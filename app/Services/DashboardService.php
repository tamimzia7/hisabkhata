<?php

namespace App\Services;

use App\Models\Mess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly SettlementService $settlements) {}

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

        $allDeposits = (float) $mess->deposits()
            ->get()
            ->sum(fn ($deposit): float => $deposit->signedAmount());
        $allBazar = (float) $mess->bazarRecords()->sum('total_amount');
        $allExpenses = (float) $mess->expenses()->sum('amount');

        $periodBazar = (float) $mess->bazarRecords()
            ->whereBetween('purchase_date', [$fromDate, $toDate])
            ->sum('total_amount');
        $periodDeposits = (float) $mess->deposits()
            ->whereBetween('deposit_date', [$fromDate, $toDate])
            ->get()
            ->sum(fn ($deposit): float => $deposit->signedAmount());
        $periodExpenses = (float) $mess->expenses()
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->sum('amount');

        $periodMeals = (float) $mess->mealEntries()
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->sum('total_meals');

        $periodRate = $periodMeals > 0 ? round(($periodBazar + $this->mealExpenses($mess, $fromDate, $toDate)) / $periodMeals, 2) : 0.0;

        $accountBalance = $mess->accounts()->get()->sum(fn ($account): float => $account->balance());

        $outstanding = $preview['members']
            ->filter(fn (array $row): bool => $row['balance'] < 0)
            ->sum(fn (array $row): float => abs($row['balance']));

        $activeMembers = $mess->activeMembers()->count();

        $labels = [];
        $income = [];
        $expense = [];

        for ($i = 5; $i >= 0; $i--) {
            $cursor = CarbonImmutable::now()->subMonths($i);
            $start = $cursor->startOfMonth()->toDateString();
            $end = $cursor->endOfMonth()->toDateString();

            $labels[] = $cursor->format('M Y');
            $income[] = round((float) $mess->deposits()
                ->whereBetween('deposit_date', [$start, $end])
                ->get()
                ->sum(fn ($deposit): float => $deposit->signedAmount()), 2);
            $expense[] = round(
                (float) $mess->bazarRecords()->whereBetween('purchase_date', [$start, $end])->sum('total_amount')
                + (float) $mess->expenses()->whereBetween('expense_date', [$start, $end])->sum('amount'),
                2
            );
        }

        $dailyBazar = $mess->bazarRecords()
            ->whereBetween('purchase_date', [$fromDate, $toDate])
            ->selectRaw('purchase_date, SUM(total_amount) as total')
            ->groupBy('purchase_date')
            ->orderBy('purchase_date')
            ->get();

        $mealRows = $mess->mealEntries()
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
            ->with(['purchaser', 'account'])
            ->latest('purchase_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentDeposits = $mess->deposits()
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
                'total_balance' => round($allDeposits - $allBazar - $allExpenses, 2),
                'account_balance' => round($accountBalance, 2),
                'period_bazar' => round($periodBazar, 2),
                'period_meals' => $periodMeals,
                'meal_rate' => $periodRate,
                'total_members' => $activeMembers,
                'outstanding_dues' => round((float) $outstanding, 2),
                'period_deposits' => round($periodDeposits, 2),
                'period_expenses' => round($periodExpenses, 2),
            ],
            'income_expense' => [
                'labels' => $labels,
                'income' => $income,
                'expense' => $expense,
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
            'transactions' => $mess->transactions()->latest('transaction_date')->latest('id')->limit(8)->get(),
        ];
    }

    /**
     * Total meal-related expense amount in a date range.
     */
    private function mealExpenses(Mess $mess, string $from, string $to): float
    {
        return (float) $mess->expenses()
            ->whereBetween('expense_date', [$from, $to])
            ->where('is_meal_related', true)
            ->sum('amount');
    }
}
