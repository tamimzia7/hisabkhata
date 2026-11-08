<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ReopenSettlementRequest;
use App\Http\Requests\Student\SettlementRequest;
use App\Models\Mess;
use App\Models\MonthlySettlement;
use App\Services\AuditLogger;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MonthlySettlementController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlements,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Show the live settlement preview and version history.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('viewReports', $mess);

        $month = (string) $request->query('month', now()->format('Y-m'));

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = now()->format('Y-m');
        }

        return view('student.settlements.index', [
            'mess' => $mess,
            'month' => $month,
            'preview' => $this->settlements->preview($mess, $month),
            'history' => $mess->settlements()->with('finalizer')->where('month', $month)->latest('version')->get(),
            'current' => $mess->settlementForMonth($month),
            'locked' => $this->settlements->isLocked($mess, $month),
            'canManage' => $request->user()->can('manageSettlements', $mess),
        ]);
    }

    /**
     * Show a finalized or draft settlement snapshot.
     */
    public function show(Mess $mess, MonthlySettlement $settlement): View
    {
        abort_unless($settlement->mess_id === $mess->getKey(), 404);
        Gate::authorize('viewReports', $mess);

        return view('student.settlements.show', [
            'mess' => $mess,
            'settlement' => $settlement->load(['details.member', 'finalizer']),
            'canManage' => request()->user()->can('manageSettlements', $mess),
        ]);
    }

    /**
     * Finalize a month.
     */
    public function finalize(SettlementRequest $request, Mess $mess): RedirectResponse
    {
        $settlement = $this->settlements->finalize($mess, $request->validated('month'), $request->user());

        $this->audit->log($mess, $request->user(), 'settlement.finalized', $settlement, [
            'month' => $settlement->month,
            'version' => $settlement->version,
        ], $request->ip());

        return redirect()
            ->route('student.mess.settlements.show', [$mess, $settlement])
            ->with('toast', __('student.settlements.finalized'));
    }

    /**
     * Reopen a finalized month and start a new draft version.
     */
    public function reopen(ReopenSettlementRequest $request, Mess $mess, MonthlySettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->mess_id === $mess->getKey(), 404);

        $draft = $this->settlements->reopen($mess, $settlement, $request->user(), $request->validated('reason'));

        $this->audit->log($mess, $request->user(), 'settlement.reopened', $settlement, [
            'month' => $settlement->month,
            'reason' => $request->validated('reason'),
            'new_version' => $draft->version,
        ], $request->ip());

        return redirect()
            ->route('student.mess.settlements.index', [$mess, 'month' => $settlement->month])
            ->with('toast', __('student.settlements.reopened'));
    }
}
