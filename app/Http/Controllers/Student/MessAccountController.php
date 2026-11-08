<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AccountRequest;
use App\Http\Requests\Student\TransferRequest;
use App\Models\Mess;
use App\Models\MessAccount;
use App\Services\AuditLogger;
use App\Services\MessLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessAccountController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MessLedger $ledger,
    ) {}

    /**
     * Show money accounts and their ledger-derived balances.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $accounts = $mess->accounts()->orderBy('id')->get();

        return view('student.accounts.index', [
            'mess' => $mess,
            'accounts' => $accounts,
            'totalBalance' => round($accounts->sum(fn (MessAccount $account): float => $account->balance()), 2),
            'transactions' => $mess->transactions()->with('account')->latest('transaction_date')->latest('id')->limit(20)->get(),
            'canManage' => $request->user()->can('manageFinance', $mess),
        ]);
    }

    /**
     * Create a money account.
     */
    public function store(AccountRequest $request, Mess $mess): RedirectResponse
    {
        $account = $mess->accounts()->create([
            ...$request->validated(),
            'opening_balance' => $request->validated('opening_balance') ?? 0,
            'is_active' => (bool) ($request->validated('is_active') ?? true),
        ]);

        $this->audit->log($mess, $request->user(), 'account.created', $account, ['name' => $account->name], $request->ip());

        return back()->with('toast', __('student.accounts.created'));
    }

    /**
     * Update a money account.
     */
    public function update(AccountRequest $request, Mess $mess, MessAccount $account): RedirectResponse
    {
        abort_unless($account->mess_id === $mess->getKey(), 404);

        $account->update([
            ...$request->validated(),
            'opening_balance' => $request->validated('opening_balance') ?? $account->opening_balance,
            'is_active' => (bool) ($request->validated('is_active') ?? $account->is_active),
        ]);

        $this->audit->log($mess, $request->user(), 'account.updated', $account, ['name' => $account->name], $request->ip());

        return back()->with('toast', __('student.accounts.updated'));
    }

    /**
     * Transfer money between two accounts.
     */
    public function transfer(TransferRequest $request, Mess $mess): RedirectResponse
    {
        $from = $mess->accounts()->findOrFail($request->validated('from_account_id'));
        $to = $mess->accounts()->findOrFail($request->validated('to_account_id'));

        $this->ledger->transfer(
            $mess,
            $request->user(),
            $from,
            $to,
            (float) $request->validated('amount'),
            $request->validated('transaction_date'),
            $request->validated('description'),
        );

        $this->audit->log($mess, $request->user(), 'account.transferred', null, [
            'from' => $from->name,
            'to' => $to->name,
            'amount' => (float) $request->validated('amount'),
        ], $request->ip());

        return back()->with('toast', __('student.accounts.transferred'));
    }
}
