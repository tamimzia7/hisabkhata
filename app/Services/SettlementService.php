<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessExpense;
use App\Models\MessMember;
use App\Models\MonthlySettlement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    /**
     * Build the live settlement figures for a mess and billing month.
     *
     * @return array{
     *     month: string,
     *     from: string,
     *     to: string,
     *     members: Collection<int, array{member: MessMember, meals: float, meal_cost: float, deposits: float, other_allocated: float, adjustments: float, balance: float}>,
     *     totals: array<string, float>
     * }
     */
    public function preview(Mess $mess, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();
        $from = $start->toDateString();
        $to = $end->toDateString();

        $members = $mess->members()->orderBy('name')->get();

        $mealRows = $mess->mealEntries()
            ->whereBetween('entry_date', [$from, $to])
            ->selectRaw('mess_member_id, SUM(total_meals) as meals')
            ->groupBy('mess_member_id')
            ->pluck('meals', 'mess_member_id');

        $totalMeals = round((float) $mealRows->sum(), 2);
        $totalBazar = round((float) $mess->bazarRecords()->whereBetween('purchase_date', [$from, $to])->sum('total_amount'), 2);
        $totalMealExpenses = round((float) $mess->expenses()->whereBetween('expense_date', [$from, $to])->where('is_meal_related', true)->sum('amount'), 2);
        $totalOtherExpenses = round((float) $mess->expenses()->whereBetween('expense_date', [$from, $to])->where('is_meal_related', false)->sum('amount'), 2);

        $mealPool = round($totalBazar + $totalMealExpenses, 2);
        $mealRate = $totalMeals > 0 ? round($mealPool / $totalMeals, 4) : 0.0;

        $depositTotals = $mess->deposits()
            ->whereBetween('deposit_date', [$from, $to])
            ->get()
            ->groupBy('mess_member_id')
            ->map(fn (Collection $rows): float => round((float) $rows->sum(fn ($deposit): float => $deposit->signedAmount()), 2));

        $otherAllocations = $this->allocateOtherExpenses($mess, $from, $to, $members, $mealRows, $totalMeals);

        $rows = $members->map(function (MessMember $member) use ($mealRows, $mealRate, $depositTotals, $otherAllocations): array {
            $meals = round((float) ($mealRows[$member->getKey()] ?? 0), 2);
            $mealCost = round($meals * $mealRate, 2);
            $deposits = round((float) ($depositTotals[$member->getKey()] ?? 0), 2);
            $other = round((float) ($otherAllocations[$member->getKey()] ?? 0), 2);

            return [
                'member' => $member,
                'meals' => $meals,
                'meal_cost' => $mealCost,
                'deposits' => $deposits,
                'other_allocated' => $other,
                'adjustments' => 0.0,
                'balance' => round($deposits - $mealCost - $other, 2),
            ];
        });

        $totalDeposits = round((float) $rows->sum('deposits'), 2);

        return [
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'members' => $rows,
            'totals' => [
                'total_deposits' => $totalDeposits,
                'total_bazar' => $totalBazar,
                'total_meal_expenses' => $totalMealExpenses,
                'total_other_expenses' => $totalOtherExpenses,
                'total_meals' => $totalMeals,
                'meal_rate' => $mealRate,
                'total_meal_cost' => round((float) $rows->sum('meal_cost'), 2),
                'total_other_allocated' => round((float) $rows->sum('other_allocated'), 2),
                'total_surplus' => round($totalDeposits - $mealPool - $totalOtherExpenses, 2),
            ],
        ];
    }

    /**
     * Finalize a month, storing an immutable settlement snapshot.
     */
    public function finalize(Mess $mess, string $month, User $actor): MonthlySettlement
    {
        if ($mess->finalizedSettlementForMonth($month) !== null) {
            throw ValidationException::withMessages([
                'month' => __('student.errors.already_finalized'),
            ]);
        }

        $preview = $this->preview($mess, $month);

        return DB::transaction(function () use ($mess, $month, $actor, $preview): MonthlySettlement {
            $alreadyFinalized = $mess->settlements()
                ->where('month', $month)
                ->where('status', MonthlySettlement::STATUS_FINALIZED)
                ->lockForUpdate()
                ->exists();

            if ($alreadyFinalized) {
                throw ValidationException::withMessages([
                    'month' => __('student.errors.already_finalized'),
                ]);
            }

            $version = (int) $mess->settlements()->where('month', $month)->max('version') + 1;

            $settlement = $mess->settlements()
                ->where('month', $month)
                ->where('status', MonthlySettlement::STATUS_DRAFT)
                ->latest('version')
                ->lockForUpdate()
                ->first();

            if ($settlement === null) {
                $settlement = MonthlySettlement::create([
                    'mess_id' => $mess->getKey(),
                    'month' => $month,
                    'version' => $version,
                    'status' => MonthlySettlement::STATUS_FINALIZED,
                    'total_deposits' => $preview['totals']['total_deposits'],
                    'total_bazar' => $preview['totals']['total_bazar'],
                    'total_meal_expenses' => $preview['totals']['total_meal_expenses'],
                    'total_other_expenses' => $preview['totals']['total_other_expenses'],
                    'total_meals' => $preview['totals']['total_meals'],
                    'meal_rate' => $preview['totals']['meal_rate'],
                    'total_surplus' => $preview['totals']['total_surplus'],
                    'finalized_at' => now(),
                    'finalized_by' => $actor->getKey(),
                ]);
            } else {
                $settlement->update([
                    'status' => MonthlySettlement::STATUS_FINALIZED,
                    'total_deposits' => $preview['totals']['total_deposits'],
                    'total_bazar' => $preview['totals']['total_bazar'],
                    'total_meal_expenses' => $preview['totals']['total_meal_expenses'],
                    'total_other_expenses' => $preview['totals']['total_other_expenses'],
                    'total_meals' => $preview['totals']['total_meals'],
                    'meal_rate' => $preview['totals']['meal_rate'],
                    'total_surplus' => $preview['totals']['total_surplus'],
                    'finalized_at' => now(),
                    'finalized_by' => $actor->getKey(),
                ]);
                $settlement->details()->delete();
            }

            foreach ($preview['members'] as $row) {
                $settlement->details()->create([
                    'mess_member_id' => $row['member']->getKey(),
                    'meals' => $row['meals'],
                    'meal_cost' => $row['meal_cost'],
                    'deposits' => $row['deposits'],
                    'other_allocated' => $row['other_allocated'],
                    'adjustments' => $row['adjustments'],
                    'balance' => $row['balance'],
                ]);
            }

            return $settlement;
        });
    }

    /**
     * Reopen a finalized month. The historical snapshot is preserved and a new draft is created.
     */
    public function reopen(Mess $mess, MonthlySettlement $settlement, User $actor, string $reason): MonthlySettlement
    {
        if (! $settlement->isFinalized()) {
            throw ValidationException::withMessages([
                'settlement' => __('student.errors.not_finalized'),
            ]);
        }

        $preview = $this->preview($mess, $settlement->month);

        return DB::transaction(function () use ($mess, $settlement, $actor, $reason, $preview): MonthlySettlement {
            $settlement->update([
                'status' => MonthlySettlement::STATUS_SUPERSEDED,
                'reopened_at' => now(),
                'reopened_by' => $actor->getKey(),
                'reopen_reason' => $reason,
            ]);

            $version = (int) $mess->settlements()->where('month', $settlement->month)->max('version') + 1;

            return MonthlySettlement::create([
                'mess_id' => $mess->getKey(),
                'month' => $settlement->month,
                'version' => $version,
                'status' => MonthlySettlement::STATUS_DRAFT,
                'total_deposits' => $preview['totals']['total_deposits'],
                'total_bazar' => $preview['totals']['total_bazar'],
                'total_meal_expenses' => $preview['totals']['total_meal_expenses'],
                'total_other_expenses' => $preview['totals']['total_other_expenses'],
                'total_meals' => $preview['totals']['total_meals'],
                'meal_rate' => $preview['totals']['meal_rate'],
                'total_surplus' => $preview['totals']['total_surplus'],
            ]);
        });
    }

    /**
     * Determine whether the month is locked by a finalized settlement.
     */
    public function isLocked(Mess $mess, string $month): bool
    {
        return $mess->settlements()
            ->where('month', $month)
            ->where('status', MonthlySettlement::STATUS_FINALIZED)
            ->exists();
    }

    /**
     * Abort a financial mutation when its month is already finalized.
     */
    public function ensureMonthOpen(Mess $mess, string $date, string $field = 'date'): void
    {
        if ($this->isLocked($mess, substr($date, 0, 7))) {
            throw ValidationException::withMessages([
                $field => __('student.errors.month_locked'),
            ]);
        }
    }

    /**
     * Allocate non-meal expenses to members.
     *
     * @param  Collection<int, MessMember>  $members
     * @param  Collection<int|string, mixed>  $mealRows
     * @return array<int, float>
     */
    private function allocateOtherExpenses(Mess $mess, string $from, string $to, Collection $members, Collection $mealRows, float $totalMeals): array
    {
        $active = $members->where('status', MessMember::STATUS_ACTIVE);
        $eligible = $active->isEmpty() ? $members : $active;
        $allocations = [];

        foreach ($eligible as $member) {
            $allocations[$member->getKey()] = 0.0;
        }

        $expenses = $mess->expenses()
            ->whereBetween('expense_date', [$from, $to])
            ->where('is_meal_related', false)
            ->get();

        foreach ($expenses as $expense) {
            $amount = (float) $expense->amount;

            if ($eligible->isEmpty()) {
                continue;
            }

            if ($expense->allocation_method === MessExpense::ALLOCATION_MEALS && $totalMeals > 0) {
                foreach ($eligible as $member) {
                    $meals = (float) ($mealRows[$member->getKey()] ?? 0);
                    $allocations[$member->getKey()] += round($amount * ($meals / $totalMeals), 2);
                }

                continue;
            }

            $share = round($amount / $eligible->count(), 2);

            foreach ($eligible as $member) {
                $allocations[$member->getKey()] += $share;
            }
        }

        return $allocations;
    }
}
