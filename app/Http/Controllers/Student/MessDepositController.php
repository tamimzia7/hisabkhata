<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\DepositRequest;
use App\Models\Mess;
use App\Models\MessDeposit;
use App\Models\MessTransaction;
use App\Services\AuditLogger;
use App\Services\MessLedger;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessDepositController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MessLedger $ledger,
        private readonly SettlementService $settlements,
    ) {}

    /**
     * List deposits with member-wise summary.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $month = $this->month($request);
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $deposits = $mess->deposits()
            ->with(['member', 'account'])
            ->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('member'), fn ($query) => $query->where('mess_member_id', $request->integer('member')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->latest('deposit_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $monthRows = $mess->deposits()
            ->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $byMember = $monthRows->groupBy('mess_member_id')->map(
            fn ($rows): float => round((float) $rows->sum(fn ($deposit): float => $deposit->signedAmount()), 2)
        );

        return view('student.deposits.index', [
            'mess' => $mess,
            'month' => $month,
            'deposits' => $deposits,
            'members' => $mess->members()->orderBy('name')->get(),
            'accounts' => $mess->accounts()->where('is_active', true)->orderBy('name')->get(),
            'byMember' => $byMember,
            'monthTotal' => round((float) $byMember->sum(), 2),
            'locked' => $this->settlements->isLocked($mess, $month),
            'canManage' => $request->user()->can('manageFinance', $mess),
            'types' => MessDeposit::TYPES,
        ]);
    }

    /**
     * Record a deposit, refund, or adjustment.
     */
    public function store(DepositRequest $request, Mess $mess): RedirectResponse
    {
        $validated = $request->validated();
        $this->assertMemberSubmissionAllowed($request, $mess, $validated);
        $this->settlements->ensureMonthOpen($mess, $validated['deposit_date'], 'deposit_date');

        $deposit = DB::transaction(function () use ($request, $mess, $validated): MessDeposit {
            $deposit = $mess->deposits()->create([
                ...$validated,
                'created_by' => $request->user()->getKey(),
            ]);

            $direction = $deposit->type === MessDeposit::TYPE_REFUND
                ? MessTransaction::DIRECTION_DEBIT
                : MessTransaction::DIRECTION_CREDIT;

            $type = match ($deposit->type) {
                MessDeposit::TYPE_REFUND => MessTransaction::TYPE_REFUND,
                MessDeposit::TYPE_ADJUSTMENT => MessTransaction::TYPE_ADJUSTMENT,
                default => MessTransaction::TYPE_DEPOSIT,
            };

            if ($deposit->account_id !== null) {
                $this->ledger->post($mess, $request->user(), $type, $direction, (float) $deposit->amount, $deposit->deposit_date->toDateString(), $deposit->account, __('student.ledger.'.$deposit->type), $deposit);
            }

            return $deposit;
        });

        $this->audit->log($mess, $request->user(), 'deposit.created', $deposit, ['amount' => (float) $deposit->amount, 'type' => $deposit->type], $request->ip());

        return back()->with('toast', __('student.deposits.created'));
    }

    /**
     * Update a deposit record and its ledger entry.
     */
    public function update(DepositRequest $request, Mess $mess, MessDeposit $deposit): RedirectResponse
    {
        $this->ensureFinanceManager($request, $mess);
        $this->ensureBelongs($mess, $deposit);
        $validated = $request->validated();
        $this->settlements->ensureMonthOpen($mess, $deposit->deposit_date->toDateString(), 'deposit_date');
        $this->settlements->ensureMonthOpen($mess, $validated['deposit_date'], 'deposit_date');

        DB::transaction(function () use ($request, $mess, $deposit, $validated): void {
            $deposit->update($validated);
            $deposit->load('account');
            $this->ledger->removeFor($deposit);

            if ($deposit->account_id !== null) {
                $direction = $deposit->type === MessDeposit::TYPE_REFUND
                    ? MessTransaction::DIRECTION_DEBIT
                    : MessTransaction::DIRECTION_CREDIT;

                $type = match ($deposit->type) {
                    MessDeposit::TYPE_REFUND => MessTransaction::TYPE_REFUND,
                    MessDeposit::TYPE_ADJUSTMENT => MessTransaction::TYPE_ADJUSTMENT,
                    default => MessTransaction::TYPE_DEPOSIT,
                };

                $this->ledger->post($mess, $request->user(), $type, $direction, (float) $deposit->amount, $deposit->deposit_date->toDateString(), $deposit->account, __('student.ledger.'.$deposit->type), $deposit);
            }
        });

        $this->audit->log($mess, $request->user(), 'deposit.updated', $deposit, ['amount' => (float) $deposit->amount], $request->ip());

        return back()->with('toast', __('student.deposits.updated'));
    }

    /**
     * Delete a deposit record.
     */
    public function destroy(Request $request, Mess $mess, MessDeposit $deposit): RedirectResponse
    {
        $this->ensureFinanceManager($request, $mess);
        $this->ensureBelongs($mess, $deposit);
        $this->settlements->ensureMonthOpen($mess, $deposit->deposit_date->toDateString(), 'deposit_date');

        DB::transaction(function () use ($deposit): void {
            $this->ledger->removeFor($deposit);
            $deposit->delete();
        });

        $this->audit->log($mess, $request->user(), 'deposit.deleted', $deposit, ['amount' => (float) $deposit->amount], $request->ip());

        return back()->with('toast', __('student.deposits.deleted'));
    }

    private function ensureBelongs(Mess $mess, MessDeposit $deposit): void
    {
        abort_unless($deposit->mess_id === $mess->getKey(), 404);
    }

    /**
     * Members may only submit a plain deposit for their own membership.
     *
     * @param  array<string, mixed>  $validated
     */
    private function assertMemberSubmissionAllowed(DepositRequest $request, Mess $mess, array $validated): void
    {
        if ($request->user()->can('manageFinance', $mess)) {
            return;
        }

        $own = $mess->memberFor($request->user());

        abort_unless($own !== null && $own->getKey() === (int) $validated['mess_member_id'], 403);
        abort_unless($validated['type'] === MessDeposit::TYPE_DEPOSIT, 403);
    }

    /**
     * Require finance management rights for balance-changing operations.
     */
    private function ensureFinanceManager(Request $request, Mess $mess): void
    {
        abort_unless($request->user()->can('manageFinance', $mess), 403);
    }

    private function month(Request $request): string
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : now()->format('Y-m');
    }
}
