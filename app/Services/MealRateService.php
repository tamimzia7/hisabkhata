<?php

namespace App\Services;

use App\Models\Mess;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class MealRateService
{
    /**
     * Calculate the monthly meal rate and per-member meal costs using integer paisa.
     *
     * @return array<string, mixed>
     */
    public function forMonth(Mess $mess, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();
        $from = $start->toDateString();
        $to = $end->toDateString();

        $bazarCents = $this->sumCents(
            $mess->bazarRecords()->active()->whereBetween('purchase_date', [$from, $to]),
            'total_amount'
        );

        $mealExpenseCents = $this->sumCents(
            $mess->expenses()
                ->active()
                ->where('is_meal_related', true)
                ->where('is_personal', false)
                ->whereBetween('expense_date', [$from, $to]),
            'amount'
        );

        $totalMealCostCents = $bazarCents + $mealExpenseCents;

        $memberMeals = $mess->mealEntries()
            ->active()
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('mess_member_id, SUM(total_meals) as meals')
            ->groupBy('mess_member_id')
            ->pluck('meals', 'mess_member_id')
            ->map(fn ($meals): float => round((float) $meals, 2))
            ->all();

        $totalMeals = round(array_sum($memberMeals), 2);
        $rateCents = $totalMeals > 0 ? (int) round($totalMealCostCents / $totalMeals) : 0;

        $memberCostCents = [];

        foreach ($memberMeals as $memberId => $meals) {
            $memberCostCents[$memberId] = (int) round($meals * $rateCents);
        }

        return [
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'bazar_cents' => $bazarCents,
            'meal_expense_cents' => $mealExpenseCents,
            'total_meal_cost_cents' => $totalMealCostCents,
            'rate_cents' => $rateCents,
            'total_meals' => $totalMeals,
            'member_meals' => $memberMeals,
            'member_cost_cents' => $memberCostCents,
            'bazar' => $this->fromCents($bazarCents),
            'meal_expenses' => $this->fromCents($mealExpenseCents),
            'total_meal_cost' => $this->fromCents($totalMealCostCents),
            'meal_rate' => $this->fromCents($rateCents),
            'member_costs' => array_map(fn (int $cents): float => $this->fromCents($cents), $memberCostCents),
        ];
    }

    /**
     * Convert a decimal amount into integer paisa.
     */
    public function toCents(int|float|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * Convert integer paisa into a two-decimal float.
     */
    public function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Sum a decimal column as integer paisa.
     */
    private function sumCents(Builder|Relation $query, string $column): int
    {
        return $this->toCents($query->sum($column));
    }
}
