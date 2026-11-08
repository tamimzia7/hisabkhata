<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessDeposit;
use App\Models\MessExpense;
use App\Models\MessMember;
use App\Models\MonthlySettlement;
use App\Models\SettlementDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    private const STATUS_RECEIVABLE = 'receivable';

    private const STATUS_DUE = 'due';

    private const STATUS_SETTLED = 'settled';

    public function __construct(private readonly MealRateService $mealRates) {}

    /**
     * Build the live settlement figures for a mess and billing month.
     *
     * @return array{
     *     month: string,
     *     from: string,
     *     to: string,
     *     members: Collection<int, array<string, mixed>>,
     *     totals: array<string, float>
     * }
     */
    public function preview(Mess $mess, string $month): array
    {
        $rate = $this->mealRates->forMonth($mess, $month);
        $from = $rate['from'];
        $to = $rate['to'];
        $members = $mess->members()->orderBy('name')->get();
        $memberIds = $members->pluck('id')->map(fn (int $id): int => $id)->all();

        $openings = $this->openingBalances($mess, $month);

        $deposits = $this->zeroMap($memberIds);
        $refunds = $this->zeroMap($memberIds);
        $adjustments = $this->zeroMap($memberIds);

        $depositRows = $mess->deposits()
            ->active()
            ->whereBetween('deposit_date', [$from, $to])
            ->get();

        foreach ($depositRows as $deposit) {
            $memberId = (int) $deposit->mess_member_id;
            $cents = $this->mealRates->toCents($deposit->amount);

            match ($deposit->type) {
                MessDeposit::TYPE_REFUND => $refunds[$memberId] = ($refunds[$memberId] ?? 0) + $cents,
                MessDeposit::TYPE_ADJUSTMENT => $adjustments[$memberId] = ($adjustments[$memberId] ?? 0) + $cents,
                default => $deposits[$memberId] = ($deposits[$memberId] ?? 0) + $cents,
            };
        }

        $sharedAllocations = $this->allocateSharedExpenses($mess, $from, $to, $members, $rate['member_meals'], $rate['total_meals']);
        [$personalExpenses, $totalPersonalCents] = $this->personalExpenses($mess, $from, $to, $memberIds);

        $rows = $members->map(function (MessMember $member) use ($openings, $deposits, $refunds, $adjustments, $rate, $sharedAllocations, $personalExpenses): array {
            $id = (int) $member->getKey();
            $meals = round((float) ($rate['member_meals'][$id] ?? 0), 2);
            $mealCost = (int) ($rate['member_cost_cents'][$id] ?? 0);
            $opening = $openings[$id] ?? 0;
            $balance = $opening
                + ($deposits[$id] ?? 0)
                + ($refunds[$id] ?? 0)
                + ($adjustments[$id] ?? 0)
                - $mealCost
                - ($sharedAllocations[$id] ?? 0)
                - ($personalExpenses[$id] ?? 0);

            return [
                'member' => $member,
                'opening_balance' => $this->mealRates->fromCents($opening),
                'deposits' => $this->mealRates->fromCents($deposits[$id] ?? 0),
                'refunds' => $this->mealRates->fromCents($refunds[$id] ?? 0),
                'adjustments' => $this->mealRates->fromCents($adjustments[$id] ?? 0),
                'meals' => $meals,
                'meal_cost' => $this->mealRates->fromCents($mealCost),
                'shared_allocated' => $this->mealRates->fromCents($sharedAllocations[$id] ?? 0),
                'other_allocated' => $this->mealRates->fromCents($sharedAllocations[$id] ?? 0),
                'personal_expenses' => $this->mealRates->fromCents($personalExpenses[$id] ?? 0),
                'balance' => $this->mealRates->fromCents($balance),
                'status' => $this->statusForCents($balance),
            ];
        });

        $totalDeposits = (int) array_sum($deposits);
        $totalRefunds = (int) array_sum($refunds);
        $totalAdjustments = (int) array_sum($adjustments);
        $totalMealCost = (int) $rows->sum(fn (array $row): int => $this->mealRates->toCents($row['meal_cost']));
        $totalShared = (int) array_sum($sharedAllocations);
        $totalPersonal = $totalPersonalCents;
        $totalOpening = (int) array_sum($openings);

        $outstanding = (int) $rows->sum(fn (array $row): int => $this->mealRates->toCents($row['balance']) < 0
            ? abs($this->mealRates->toCents($row['balance']))
            : 0);
        $receivable = (int) $rows->sum(fn (array $row): int => $this->mealRates->toCents($row['balance']) > 0
            ? $this->mealRates->toCents($row['balance'])
            : 0);

        $surplus = $totalDeposits + $totalRefunds + $totalAdjustments
            - $totalMealCost - $totalShared - $totalPersonal;

        return [
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'members' => $rows,
            'totals' => [
                'total_opening_balance' => $this->mealRates->fromCents($totalOpening),
                'total_deposits' => $this->mealRates->fromCents($totalDeposits),
                'total_refunds' => $this->mealRates->fromCents($totalRefunds),
                'total_adjustments' => $this->mealRates->fromCents($totalAdjustments),
                'total_bazar' => $rate['bazar'],
                'total_meal_expenses' => $rate['meal_expenses'],
                'total_meal_cost' => $this->mealRates->fromCents($totalMealCost),
                'total_shared_expenses' => $this->mealRates->fromCents($totalShared),
                'total_other_expenses' => $this->mealRates->fromCents($totalShared),
                'total_personal_expenses' => $this->mealRates->fromCents($totalPersonal),
                'total_other_allocated' => $this->mealRates->fromCents($totalShared),
                'total_meals' => $rate['total_meals'],
                'meal_rate' => $rate['meal_rate'],
                'total_outstanding_dues' => $this->mealRates->fromCents($outstanding),
                'total_receivable' => $this->mealRates->fromCents($receivable),
                'total_surplus' => $this->mealRates->fromCents($surplus),
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

            $totals = $this->settlementTotals($preview);

            if ($settlement === null) {
                $settlement = MonthlySettlement::create([
                    'mess_id' => $mess->getKey(),
                    'month' => $month,
                    'version' => $version,
                    'status' => MonthlySettlement::STATUS_FINALIZED,
                    ...$totals,
                    'finalized_at' => now(),
                    'finalized_by' => $actor->getKey(),
                ]);
            } else {
                $settlement->update([
                    'status' => MonthlySettlement::STATUS_FINALIZED,
                    ...$totals,
                    'finalized_at' => now(),
                    'finalized_by' => $actor->getKey(),
                ]);
                $settlement->details()->delete();
            }

            foreach ($preview['members'] as $row) {
                $settlement->details()->create([
                    'mess_member_id' => $row['member']->getKey(),
                    'opening_balance' => $row['opening_balance'],
                    'meals' => $row['meals'],
                    'meal_cost' => $row['meal_cost'],
                    'deposits' => $row['deposits'],
                    'refunds' => $row['refunds'],
                    'other_allocated' => $row['shared_allocated'],
                    'personal_expenses' => $row['personal_expenses'],
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
                ...$this->settlementTotals($preview),
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
     * Opening balances come from each member's most recent finalized settlement.
     *
     * @return array<int, int>
     */
    private function openingBalances(Mess $mess, string $month): array
    {
        return SettlementDetail::query()
            ->join('monthly_settlements as ms', 'ms.id', '=', 'settlement_details.monthly_settlement_id')
            ->where('ms.mess_id', $mess->getKey())
            ->where('ms.status', MonthlySettlement::STATUS_FINALIZED)
            ->where('ms.month', '<', $month)
            ->orderByDesc('ms.month')
            ->orderByDesc('ms.version')
            ->get(['settlement_details.mess_member_id', 'settlement_details.balance'])
            ->unique('mess_member_id')
            ->mapWithKeys(fn ($row): array => [(int) $row->mess_member_id => $this->mealRates->toCents($row->balance)])
            ->all();
    }

    /**
     * Allocate non-meal shared expenses to active members.
     *
     * @param  Collection<int, MessMember>  $members
     * @param  array<int|string, float>  $memberMeals
     * @return array<int, int>
     */
    private function allocateSharedExpenses(Mess $mess, string $from, string $to, Collection $members, array $memberMeals, float $totalMeals): array
    {
        $eligible = $members->where('status', MessMember::STATUS_ACTIVE)->sortBy('id')->values();

        if ($eligible->isEmpty()) {
            $eligible = $members->sortBy('id')->values();
        }

        $allocations = $this->zeroMap($members->pluck('id')->all());

        $expenses = $mess->expenses()
            ->active()
            ->whereBetween('expense_date', [$from, $to])
            ->where('is_meal_related', false)
            ->where('is_personal', false)
            ->get();

        foreach ($expenses as $expense) {
            $amount = $this->mealRates->toCents($expense->amount);

            if ($eligible->isEmpty()) {
                continue;
            }

            if ($expense->allocation_method === MessExpense::ALLOCATION_MEALS && $totalMeals > 0) {
                $allocated = 0;
                $shares = [];

                foreach ($eligible as $member) {
                    $meals = (float) ($memberMeals[$member->getKey()] ?? 0);
                    $share = (int) round($amount * ($meals / $totalMeals));
                    $shares[$member->getKey()] = $share;
                    $allocated += $share;
                }

                $shares[$eligible->first()->getKey()] += $amount - $allocated;

                foreach ($shares as $memberId => $share) {
                    $allocations[$memberId] += $share;
                }

                continue;
            }

            $count = max($eligible->count(), 1);
            $share = intdiv($amount, $count);
            $remainder = $amount - ($share * $count);

            foreach ($eligible->values() as $index => $member) {
                $allocations[$member->getKey()] += $share + ($index < $remainder ? 1 : 0);
            }
        }

        return $allocations;
    }

    /**
     * Personal expenses are charged only to their owning member.
     *
     * @param  list<int>  $memberIds
     * @return array{0: array<int, int>, 1: int}
     */
    private function personalExpenses(Mess $mess, string $from, string $to, array $memberIds): array
    {
        $allocations = $this->zeroMap($memberIds);
        $total = 0;

        $expenses = $mess->expenses()
            ->active()
            ->whereBetween('expense_date', [$from, $to])
            ->where('is_personal', true)
            ->get();

        foreach ($expenses as $expense) {
            $amount = $this->mealRates->toCents($expense->amount);
            $total += $amount;

            $memberId = (int) $expense->mess_member_id;

            if ($memberId > 0) {
                $allocations[$memberId] = ($allocations[$memberId] ?? 0) + $amount;
            }
        }

        return [$allocations, $total];
    }

    /**
     * Build the persisted settlement totals from a preview.
     *
     * @param  array<string, mixed>  $preview
     * @return array<string, float>
     */
    private function settlementTotals(array $preview): array
    {
        return [
            'total_deposits' => $preview['totals']['total_deposits'],
            'total_refunds' => $preview['totals']['total_refunds'],
            'total_adjustments' => $preview['totals']['total_adjustments'],
            'total_bazar' => $preview['totals']['total_bazar'],
            'total_meal_expenses' => $preview['totals']['total_meal_expenses'],
            'total_other_expenses' => $preview['totals']['total_other_expenses'],
            'total_personal_expenses' => $preview['totals']['total_personal_expenses'],
            'total_meals' => $preview['totals']['total_meals'],
            'total_meal_cost' => $preview['totals']['total_meal_cost'],
            'meal_rate' => $preview['totals']['meal_rate'],
            'total_surplus' => $preview['totals']['total_surplus'],
            'total_outstanding_dues' => $preview['totals']['total_outstanding_dues'],
            'total_receivable' => $preview['totals']['total_receivable'],
        ];
    }

    /**
     * Create a zero map for the given member IDs.
     *
     * @param  list<int|string>  $memberIds
     * @return array<int, int>
     */
    private function zeroMap(array $memberIds): array
    {
        $map = [];

        foreach ($memberIds as $memberId) {
            $map[(int) $memberId] = 0;
        }

        return $map;
    }

    /**
     * Resolve the balance status label.
     */
    private function statusForCents(int $cents): string
    {
        return match (true) {
            $cents > 0 => self::STATUS_RECEIVABLE,
            $cents < 0 => self::STATUS_DUE,
            default => self::STATUS_SETTLED,
        };
    }
}
