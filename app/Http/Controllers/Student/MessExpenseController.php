<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ExpenseRequest;
use App\Models\Mess;
use App\Models\MessExpense;
use App\Models\MessTransaction;
use App\Services\AuditLogger;
use App\Services\MessLedger;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MessExpenseController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MessLedger $ledger,
        private readonly SettlementService $settlements,
    ) {}

    /**
     * List non-bazar expenses.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $month = $this->month($request);
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $expenses = $mess->expenses()
            ->active()
            ->with(['account', 'member'])
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('type'), function ($query) use ($request): void {
                if ($request->query('type') === 'meal') {
                    $query->where('is_meal_related', true);
                } elseif ($request->query('type') === 'other') {
                    $query->where('is_meal_related', false);
                }
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $monthExpenses = $mess->expenses()
            ->active()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return view('student.expenses.index', [
            'mess' => $mess,
            'month' => $month,
            'expenses' => $expenses,
            'members' => $mess->members()->orderBy('name')->get(),
            'accounts' => $mess->accounts()->where('is_active', true)->orderBy('name')->get(),
            'mealTotal' => round((float) $monthExpenses->where('is_meal_related', true)->sum('amount'), 2),
            'otherTotal' => round((float) $monthExpenses->where('is_meal_related', false)->sum('amount'), 2),
            'locked' => $this->settlements->isLocked($mess, $month),
            'canManage' => $request->user()->can('manageFinance', $mess),
        ]);
    }

    /**
     * Record an expense and post its ledger entry.
     */
    public function store(ExpenseRequest $request, Mess $mess): RedirectResponse
    {
        $validated = $request->validated();
        $this->settlements->ensureMonthOpen($mess, $validated['expense_date'], 'expense_date');

        $expense = DB::transaction(function () use ($request, $mess, $validated): MessExpense {
            $expense = $mess->expenses()->create([
                ...$validated,
                'is_meal_related' => (bool) ($validated['is_meal_related'] ?? false),
                'is_personal' => (bool) ($validated['is_personal'] ?? false),
                'mess_member_id' => ($validated['is_personal'] ?? false) ? ($validated['mess_member_id'] ?? null) : null,
                'created_by' => $request->user()->getKey(),
                'receipt_path' => $request->hasFile('receipt')
                    ? Storage::disk('public')->putFile('mess-expenses', $request->file('receipt'))
                    : null,
            ]);

            if ($expense->account_id !== null) {
                $this->ledger->post($mess, $request->user(), MessTransaction::TYPE_EXPENSE, MessTransaction::DIRECTION_DEBIT, (float) $expense->amount, $expense->expense_date->toDateString(), $expense->account, __('student.ledger.expense').': '.$expense->title, $expense);
            }

            return $expense;
        });

        $this->audit->log($mess, $request->user(), 'expense.created', $expense, ['amount' => (float) $expense->amount, 'meal' => $expense->is_meal_related], $request->ip());

        return back()->with('toast', __('student.expenses.created'));
    }

    /**
     * Update an expense.
     */
    public function update(ExpenseRequest $request, Mess $mess, MessExpense $expense): RedirectResponse
    {
        $this->ensureBelongs($mess, $expense);
        $validated = $request->validated();
        $this->settlements->ensureMonthOpen($mess, $expense->expense_date->toDateString(), 'expense_date');
        $this->settlements->ensureMonthOpen($mess, $validated['expense_date'], 'expense_date');

        DB::transaction(function () use ($request, $mess, $expense, $validated): void {
            if ($request->hasFile('receipt') && filled($expense->receipt_path)) {
                Storage::disk('public')->delete($expense->receipt_path);
            }

            $expense->update([
                ...$validated,
                'is_meal_related' => (bool) ($validated['is_meal_related'] ?? false),
                'is_personal' => (bool) ($validated['is_personal'] ?? false),
                'mess_member_id' => ($validated['is_personal'] ?? false) ? ($validated['mess_member_id'] ?? null) : null,
                'receipt_path' => $request->hasFile('receipt')
                    ? Storage::disk('public')->putFile('mess-expenses', $request->file('receipt'))
                    : $expense->receipt_path,
            ]);

            $expense->load('account');
            $this->ledger->removeFor($expense);

            if ($expense->account_id !== null) {
                $this->ledger->post($mess, $request->user(), MessTransaction::TYPE_EXPENSE, MessTransaction::DIRECTION_DEBIT, (float) $expense->amount, $expense->expense_date->toDateString(), $expense->account, __('student.ledger.expense').': '.$expense->title, $expense);
            }
        });

        $this->audit->log($mess, $request->user(), 'expense.updated', $expense, ['amount' => (float) $expense->amount], $request->ip());

        return back()->with('toast', __('student.expenses.updated'));
    }

    /**
     * Delete an expense.
     */
    public function destroy(Request $request, Mess $mess, MessExpense $expense): RedirectResponse
    {
        $this->ensureBelongs($mess, $expense);
        $this->settlements->ensureMonthOpen($mess, $expense->expense_date->toDateString(), 'expense_date');

        DB::transaction(function () use ($expense): void {
            $this->ledger->removeFor($expense);

            if (filled($expense->receipt_path)) {
                Storage::disk('public')->delete($expense->receipt_path);
            }

            $expense->delete();
        });

        $this->audit->log($mess, $request->user(), 'expense.deleted', $expense, ['amount' => (float) $expense->amount], $request->ip());

        return back()->with('toast', __('student.expenses.deleted'));
    }

    private function ensureBelongs(Mess $mess, MessExpense $expense): void
    {
        abort_unless($expense->mess_id === $mess->getKey(), 404);
    }

    private function month(Request $request): string
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : now()->format('Y-m');
    }
}
