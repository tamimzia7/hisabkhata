<?php

namespace App\Services;

use App\Models\Mess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly SettlementService $settlements,
        private readonly MealRateService $mealRates,
    ) {}

    /**
     * Build every dashboard figure from the database for the given date range.
     *
     * @return array<string, mixed>
     */
    public function summary(Mess $mess, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $month = $to->format('Y-m');

        $preview = $this->settlements->preview($mess, $month);
        $rate = $this->mealRates->forMonth($mess, $month);

        $allDepositCents = $this->depositCents($mess);
        $allBazarCents = $this->mealRates->toCents($mess->bazarRecords()->active()->sum('total_amount'));
        $allExpenseCents = $this->mealRates->toCents($mess->expenses()->active()->sum('amount'));

        $periodBazarCents = $this->mealRates->toCents(
            $mess->bazarRecords()->active()->whereBetween('purchase_date', [$fromDate, $toDate])->sum('total_amount')
        );
        $periodExpenseCents = $this->mealRates->toCents(
            $mess->expenses()->active()->whereBetween('expense_date', [$fromDate, $toDate])->sum('amount')
        );
        $periodDepositCents = $this->mealRates->toCents(
            $mess->deposits()->active()->whereBetween('deposit_date', [$fromDate, $toDate])->sum('amount')
        );

        $periodMeals = round((float) $mess->mealEntries()
            ->active()
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->sum('total_meals'), 2);

        $periodRateCents = $periodMeals > 0
            ? (int) round(($periodBazarCents + $this->mealExpenseCents($mess, $fromDate, $toDate)) / $periodMeals)
            : 0;

        $accountBalance = (float) $mess->accounts()->get()->sum(fn ($account): float => $account->balance());

        $outstanding = $preview['totals']['total_outstanding_dues'];
        $receivable = $preview['totals']['total_receivable'];
        $activeMembers = $mess->activeMembers()->count();

        $labels = [];
        $income = [];
        $expense = [];
        $monthlyBazar = [];
        $monthlyMeals = [];

        for ($i = 5; $i >= 0; $i--) {
            $cursor = CarbonImmutable::now()->subMonths($i);
            $start = $cursor->startOfMonth()->toDateString();
            $end = $cursor->endOfMonth()->toDateString();

            $labels[] = $cursor->format('M Y');
            $income[] = $this->mealRates->fromCents($this->mealRates->toCents(
                $mess->deposits()->active()->whereBetween('deposit_date', [$start, $end])->sum('amount')
            ));
            $monthBazarCents = $this->mealRates->toCents(
                $mess->bazarRecords()->active()->whereBetween('purchase_date', [$start, $end])->sum('total_amount')
            );
            $monthExpenseCents = $this->mealRates->toCents(
                $mess->expenses()->active()->whereBetween('expense_date', [$start, $end])->sum('amount')
            );
            $monthlyBazar[] = $this->mealRates->fromCents($monthBazarCents);
            $expense[] = $this->mealRates->fromCents($monthBazarCents + $monthExpenseCents);
            $monthlyMeals[] = round((float) $mess->mealEntries()->active()->whereBetween('entry_date', [$start, $end])->sum('total_meals'), 2);
        }

        $dailyBazar = $mess->bazarRecords()
            ->active()
            ->whereBetween('purchase_date', [$fromDate, $toDate])
            ->selectRaw('purchase_date, SUM(total_amount) as total')
            ->groupBy('purchase_date')
            ->orderBy('purchase_date')
            ->get();

        $mealRows = $mess->mealEntries()
            ->active()
            ->with('member')
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->get()
            ->groupBy('mess_member_id')
            ->map(function (Collection $rows): array {
                $member = $rows->first()->member;

                return [
                    'id' => $member?->getKey(),
                    'name' => $member?->name ?? '—',
                    'breakfast' => round((float) $rows->sum('breakfast'), 2),
                    'lunch' => round((float) $rows->sum('lunch'), 2),
                    'dinner' => round((float) $rows->sum('dinner'), 2),
                    'total' => round((float) $rows->sum('total_meals'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $recentBazar = $mess->bazarRecords()
            ->active()
            ->with(['purchaser', 'account'])
            ->latest('purchase_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentDeposits = $mess->deposits()
            ->active()
            ->with(['member', 'account'])
            ->latest('deposit_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $latestSettlement = $mess->settlements()
            ->where('month', $month)
            ->latest('version')
            ->first();

        $reminders = $preview['members']
            ->filter(fn (array $row): bool => $row['balance'] < 0)
            ->sortBy('balance')
            ->take(5)
            ->values();

        return [
            'month' => $month,
            'preview' => $preview,
            'kpis' => [
                'total_balance' => $this->mealRates->fromCents($allDepositCents - $allBazarCents - $allExpenseCents),
                'account_balance' => round($accountBalance, 2),
                'period_bazar' => $this->mealRates->fromCents($periodBazarCents),
                'period_meals' => $periodMeals,
                'meal_rate' => $this->mealRates->fromCents($periodRateCents),
                'monthly_meal_rate' => $rate['meal_rate'],
                'total_members' => $activeMembers,
                'outstanding_dues' => $outstanding,
                'receivable' => $receivable,
                'period_deposits' => $this->mealRates->fromCents($periodDepositCents),
                'period_expenses' => $this->mealRates->fromCents($periodExpenseCents),
            ],
            'income_expense' => [
                'labels' => $labels,
                'income' => $income,
                'expense' => $expense,
            ],
            'monthly_bazar' => [
                'labels' => $labels,
                'values' => $monthlyBazar,
            ],
            'monthly_meals' => [
                'labels' => $labels,
                'values' => $monthlyMeals,
            ],
            'daily_bazar' => [
                'labels' => $dailyBazar->map(fn ($row): string => (string) $row->purchase_date)->all(),
                'values' => $dailyBazar->map(fn ($row): float => round((float) $row->total, 2))->all(),
            ],
            'member_meals' => $mealRows,
            'member_balances' => $preview['members']->sortBy('balance')->values(),
            'recent_bazar' => $recentBazar,
            'recent_deposits' => $recentDeposits,
            'settlement' => $latestSettlement,
            'reminders' => $reminders,
            'recent_activity' => $this->recentActivity($mess),
            'transactions' => $mess->transactions()->active()->latest('transaction_date')->latest('id')->limit(8)->get(),
        ];
    }

    /**
     * Total active, non-personal meal-related expenses in a date range.
     */
    private function mealExpenseCents(Mess $mess, string $from, string $to): int
    {
        return $this->mealRates->toCents($mess->expenses()
            ->active()
            ->whereBetween('expense_date', [$from, $to])
            ->where('is_meal_related', true)
            ->where('is_personal', false)
            ->sum('amount'));
    }

    /**
     * All active deposit money (deposits + refunds + adjustments) as paisa.
     */
    private function depositCents(Mess $mess): int
    {
        return $this->mealRates->toCents($mess->deposits()->active()->sum('amount'));
    }

    /**
     * Merge recent operational and financial activity into one feed.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recentActivity(Mess $mess): Collection
    {
        $activity = collect();

        foreach ($mess->deposits()->active()->with('member')->latest('created_at')->limit(5)->get() as $deposit) {
            $activity->push([
                'kind' => 'deposit',
                'title' => $deposit->member?->name ?? '—',
                'detail' => __('student.deposits.types.'.$deposit->type),
                'amount' => (float) $deposit->amount,
                'date' => $deposit->deposit_date,
                'sort' => $deposit->created_at,
            ]);
        }

        foreach ($mess->bazarRecords()->active()->with('purchaser')->latest('created_at')->limit(5)->get() as $record) {
            $activity->push([
                'kind' => 'bazar',
                'title' => $record->category,
                'detail' => $record->purchaser?->name ?? '—',
                'amount' => (float) $record->total_amount,
                'date' => $record->purchase_date,
                'sort' => $record->created_at,
            ]);
        }

        foreach ($mess->mealEntries()->active()->with('member')->latest('created_at')->limit(5)->get() as $entry) {
            $activity->push([
                'kind' => 'meal',
                'title' => $entry->member?->name ?? '—',
                'detail' => localize_number($entry->total_meals).' '.__('student.reports.columns.meals'),
                'amount' => null,
                'date' => $entry->entry_date,
                'sort' => $entry->created_at,
            ]);
        }

        foreach ($mess->expenses()->active()->latest('created_at')->limit(5)->get() as $expense) {
            $activity->push([
                'kind' => 'expense',
                'title' => $expense->title,
                'detail' => $expense->is_personal ? __('student.expenses.personal') : __('student.expenses.shared'),
                'amount' => (float) $expense->amount,
                'date' => $expense->expense_date,
                'sort' => $expense->created_at,
            ]);
        }

        foreach ($mess->settlements()->where('status', 'finalized')->latest('finalized_at')->limit(3)->get() as $settlement) {
            $activity->push([
                'kind' => 'settlement',
                'title' => $settlement->month,
                'detail' => __('student.settlements.finalize'),
                'amount' => (float) $settlement->total_surplus,
                'date' => $settlement->finalized_at,
                'sort' => $settlement->finalized_at,
            ]);
        }

        return $activity->sortByDesc('sort')->take(10)->values();
    }
}
