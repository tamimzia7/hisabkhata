<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessMember;
use App\Models\MonthlySettlement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly SettlementService $settlements,
        private readonly MealRateService $mealRates,
    ) {}

    /**
     * Build every dashboard figure for the selected billing month.
     *
     * @return array<string, mixed>
     */
    public function summary(Mess $mess, string $month, ?MessMember $viewer = null): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();
        $previousMonth = $start->subMonthNoOverflow()->format('Y-m');
        $previousEnd = $start->subMonthNoOverflow()->endOfMonth();

        $selected = $this->settlements->monthly($mess, $month);
        $previous = $this->settlements->monthly($mess, $previousMonth);

        $currentBazar = (float) $selected['totals']['total_bazar'];
        $previousBazar = (float) $previous['totals']['total_bazar'];
        $currentMeals = (float) $selected['totals']['total_meals'];
        $previousMeals = (float) $previous['totals']['total_meals'];
        $currentRate = (float) $selected['totals']['meal_rate'];
        $previousRate = (float) $previous['totals']['meal_rate'];

        $balance = $this->balanceThrough($mess, $end);
        $previousBalance = $this->balanceThrough($mess, $previousEnd);

        $activeMembers = $mess->activeMembers()->count();
        $totalMembers = $mess->members()->count();
        $inactiveMembers = max(0, $totalMembers - $activeMembers);

        $dues = (float) $selected['totals']['total_outstanding_dues'];
        $previousDues = (float) $previous['totals']['total_outstanding_dues'];
        $dueMembers = $selected['members']->where('status', 'due')->count();

        $record = $mess->settlements()
            ->where('month', $month)
            ->latest('version')
            ->first();

        $settlementStatus = match (true) {
            $record?->status === MonthlySettlement::STATUS_FINALIZED => 'finalized',
            $record?->status === MonthlySettlement::STATUS_DRAFT => 'draft',
            default => 'pending',
        };

        return [
            'month' => $month,
            'month_label' => $start->format('F Y'),
            'previous_month' => $previousMonth,
            'period_start' => $start,
            'period_end' => $end,
            'members' => $selected['members']->values(),
            'kpis' => [
                'balance' => [
                    'value' => $balance,
                    'previous' => $previousBalance,
                    'change' => $this->change($balance, $previousBalance),
                ],
                'bazar' => [
                    'value' => $currentBazar,
                    'previous' => $previousBazar,
                    'change' => $this->change($currentBazar, $previousBazar),
                ],
                'meals' => [
                    'value' => $currentMeals,
                    'previous' => $previousMeals,
                    'change' => $this->change($currentMeals, $previousMeals),
                ],
                'meal_rate' => [
                    'value' => $currentRate,
                    'previous' => $previousRate,
                    'change' => $this->change($currentRate, $previousRate),
                ],
                'members' => [
                    'active' => $activeMembers,
                    'inactive' => $inactiveMembers,
                    'total' => $totalMembers,
                ],
                'dues' => [
                    'value' => $dues,
                    'previous' => $previousDues,
                    'change' => $this->change($dues, $previousDues),
                    'count' => $dueMembers,
                ],
            ],
            'charts' => [
                'income_expense' => $this->incomeExpenseSeries($mess, $start),
                'breakdown' => $this->expenseBreakdown($mess, $start),
            ],
            'activities' => $this->recentActivity($mess, $viewer),
            'settlement' => [
                'status' => $settlementStatus,
                'record' => $record,
                'is_finalized' => $settlementStatus === 'finalized',
                'period_end' => $end,
                'totals' => $selected['totals'],
            ],
        ];
    }

    /**
     * Calculate the cumulative mess balance up to the end of a date.
     */
    private function balanceThrough(Mess $mess, CarbonImmutable $end): float
    {
        $date = $end->toDateString();

        $deposits = $this->mealRates->toCents(
            $mess->deposits()->active()->whereDate('deposit_date', '<=', $date)->sum('amount')
        );
        $bazar = $this->mealRates->toCents(
            $mess->bazarRecords()->active()->whereDate('purchase_date', '<=', $date)->sum('total_amount')
        );
        $expenses = $this->mealRates->toCents(
            $mess->expenses()->active()->whereDate('expense_date', '<=', $date)->sum('amount')
        );

        return $this->mealRates->fromCents($deposits - $bazar - $expenses);
    }

    /**
     * Build the last six months of deposit vs expense data.
     *
     * @return array{labels: list<string>, deposits: list<float>, expenses: list<float>}
     */
    private function incomeExpenseSeries(Mess $mess, CarbonImmutable $selectedStart): array
    {
        $labels = [];
        $deposits = [];
        $expenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $cursor = $selectedStart->subMonthsNoOverflow($i);
            $from = $cursor->startOfMonth()->toDateString();
            $to = $cursor->endOfMonth()->toDateString();

            $labels[] = $cursor->format('M Y');
            $deposits[] = $this->mealRates->fromCents($this->mealRates->toCents(
                $mess->deposits()->active()->whereBetween('deposit_date', [$from, $to])->sum('amount')
            ));
            $bazarCents = $this->mealRates->toCents(
                $mess->bazarRecords()->active()->whereBetween('purchase_date', [$from, $to])->sum('total_amount')
            );
            $expenseCents = $this->mealRates->toCents(
                $mess->expenses()->active()->whereBetween('expense_date', [$from, $to])->sum('amount')
            );
            $expenses[] = $this->mealRates->fromCents($bazarCents + $expenseCents);
        }

        return ['labels' => $labels, 'deposits' => $deposits, 'expenses' => $expenses];
    }

    /**
     * Split the selected month's expenses into the dashboard categories.
     *
     * @return array{labels: list<string>, values: list<float>, percentages: list<float>, total: float}
     */
    private function expenseBreakdown(Mess $mess, CarbonImmutable $start): array
    {
        $from = $start->startOfMonth()->toDateString();
        $to = $start->endOfMonth()->toDateString();

        $bazar = $this->mealRates->fromCents($this->mealRates->toCents(
            $mess->bazarRecords()->active()->whereBetween('purchase_date', [$from, $to])->sum('total_amount')
        ));

        $utilities = 0;
        $miscellaneous = 0;
        $other = 0;

        $expenses = $mess->expenses()
            ->active()
            ->whereBetween('expense_date', [$from, $to])
            ->get(['category', 'amount']);

        foreach ($expenses as $expense) {
            $amount = $this->mealRates->toCents($expense->amount);
            $category = mb_strtolower(trim((string) $expense->category));

            if ($this->matchesAny($category, ['utility', 'utilities', 'electric', 'water', 'gas', 'internet', 'wifi', 'rent', 'bill', 'maintenance'])) {
                $utilities += $amount;

                continue;
            }

            if ($this->matchesAny($category, ['misc', 'miscellaneous', 'other', 'others'])) {
                $miscellaneous += $amount;

                continue;
            }

            $other += $amount;
        }

        $values = [
            $bazar,
            $this->mealRates->fromCents($other),
            $this->mealRates->fromCents($utilities),
            $this->mealRates->fromCents($miscellaneous),
        ];
        $total = round(array_sum($values), 2);
        $percentages = array_map(
            fn (float $value): float => $total > 0 ? round(($value / $total) * 100, 1) : 0.0,
            $values
        );

        return [
            'labels' => [
                __('student.dashboard.categories.bazar'),
                __('student.dashboard.categories.other'),
                __('student.dashboard.categories.utilities'),
                __('student.dashboard.categories.miscellaneous'),
            ],
            'values' => $values,
            'percentages' => $percentages,
            'total' => $total,
        ];
    }

    /**
     * Merge the latest financial and member activity into one timeline.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recentActivity(Mess $mess, ?MessMember $viewer = null): Collection
    {
        $activity = collect();

        foreach ($mess->deposits()->active()->with(['member', 'creator'])->latest('created_at')->limit(6)->get() as $deposit) {
            $activity->push([
                'kind' => 'deposit',
                'member_id' => $deposit->mess_member_id,
                'title' => __('student.dashboard.activity.deposit'),
                'description' => ($deposit->member?->name ?? '—').' · '.__('student.deposits.types.'.$deposit->type),
                'actor' => $deposit->creator?->name,
                'amount' => (float) $deposit->amount,
                'time' => $deposit->created_at,
                'url' => route('student.mess.deposits.index', $mess),
            ]);
        }

        foreach ($mess->bazarRecords()->active()->with(['purchaser', 'creator'])->latest('created_at')->limit(6)->get() as $record) {
            $activity->push([
                'kind' => 'bazar',
                'title' => __('student.dashboard.activity.bazar'),
                'description' => $record->category.' · '.($record->purchaser?->name ?? '—'),
                'actor' => $record->creator?->name,
                'amount' => (float) $record->total_amount,
                'time' => $record->created_at,
                'url' => route('student.mess.bazar.index', $mess),
            ]);
        }

        foreach ($mess->mealEntries()->active()->with(['member', 'creator', 'updater'])->latest('created_at')->limit(6)->get() as $entry) {
            $updated = $entry->updated_by !== null
                && $entry->updated_at !== null
                && $entry->created_at !== null
                && $entry->updated_at->gt($entry->created_at);

            $activity->push([
                'kind' => $updated ? 'meal_updated' : 'meal',
                'member_id' => $entry->mess_member_id,
                'title' => __($updated ? 'student.dashboard.activity.meal_updated' : 'student.dashboard.activity.meal'),
                'description' => ($entry->member?->name ?? '—').' · '.localize_number($entry->total_meals).' '.__('student.reports.columns.meals'),
                'actor' => ($updated ? $entry->updater : $entry->creator)?->name,
                'amount' => null,
                'time' => $updated ? $entry->updated_at : $entry->created_at,
                'url' => route('student.mess.meals.index', $mess),
            ]);
        }

        foreach ($mess->expenses()->active()->with(['creator', 'member'])->latest('created_at')->limit(6)->get() as $expense) {
            $activity->push([
                'kind' => 'expense',
                'title' => __('student.dashboard.activity.expense'),
                'description' => $expense->title.' · '.($expense->is_personal ? __('student.expenses.personal') : __('student.expenses.shared')),
                'actor' => $expense->creator?->name,
                'amount' => (float) $expense->amount,
                'time' => $expense->created_at,
                'url' => route('student.mess.expenses.index', $mess),
            ]);
        }

        foreach ($mess->settlements()->with('finalizer')->where('status', MonthlySettlement::STATUS_FINALIZED)->latest('finalized_at')->limit(4)->get() as $settlement) {
            $activity->push([
                'kind' => 'settlement',
                'title' => __('student.dashboard.activity.settlement'),
                'description' => $settlement->month.' · v'.$settlement->version,
                'actor' => $settlement->finalizer?->name,
                'amount' => (float) $settlement->total_surplus,
                'time' => $settlement->finalized_at,
                'url' => route('student.mess.settlements.show', [$mess, $settlement]),
            ]);
        }

        foreach ($mess->auditLogs()->with('user')->where('action', 'member.created')->latest()->limit(3)->get() as $log) {
            $activity->push([
                'kind' => 'member',
                'title' => __('student.dashboard.activity.member'),
                'description' => (string) data_get($log->meta, 'name', '—'),
                'actor' => $log->user?->name,
                'amount' => null,
                'time' => $log->created_at,
                'url' => route('student.mess.members.index', $mess),
            ]);
        }

        return $activity
            ->filter(function (array $item) use ($viewer): bool {
                if ($item['time'] === null) {
                    return false;
                }

                if ($viewer === null || $viewer->role !== MessMember::ROLE_MEMBER) {
                    return true;
                }

                if (in_array($item['kind'], ['settlement'], true)) {
                    return true;
                }

                return in_array($item['kind'], ['deposit', 'meal', 'meal_updated'], true)
                    && (int) ($item['member_id'] ?? 0) === (int) $viewer->getKey();
            })
            ->sortByDesc('time')
            ->take(12)
            ->values();
    }

    /**
     * Calculate a rounded percentage change, returning null when not meaningful.
     */
    private function change(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.005) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /**
     * Determine whether a category matches any hint fragment.
     *
     * @param  list<string>  $hints
     */
    private function matchesAny(string $category, array $hints): bool
    {
        foreach ($hints as $hint) {
            if (str_contains($category, $hint)) {
                return true;
            }
        }

        return false;
    }
}
