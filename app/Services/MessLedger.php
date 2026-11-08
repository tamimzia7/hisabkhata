<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessAccount;
use App\Models\MessTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MessLedger
{
    /**
     * Post a ledger entry.
     *
     * @param  array<string, mixed>  $meta
     */
    public function post(
        Mess $mess,
        ?User $actor,
        string $type,
        string $direction,
        float $amount,
        string $date,
        ?MessAccount $account = null,
        ?string $description = null,
        ?Model $reference = null,
        array $meta = [],
    ): MessTransaction {
        return MessTransaction::create([
            'mess_id' => $mess->getKey(),
            'account_id' => $account?->getKey(),
            'created_by' => $actor?->getKey(),
            'type' => $type,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'transaction_date' => $date,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Remove all ledger entries linked to a financial record.
     */
    public function removeFor(Model $reference): void
    {
        MessTransaction::query()
            ->where('reference_type', $reference::class)
            ->where('reference_id', $reference->getKey())
            ->delete();
    }

    /**
     * Re-post the ledger entry for a stored financial record.
     */
    public function syncFor(Model $reference, string $type, string $direction, float $amount, string $date, ?MessAccount $account, ?User $actor, ?string $description = null): void
    {
        DB::transaction(function () use ($reference, $type, $direction, $amount, $date, $account, $actor, $description): void {
            $this->removeFor($reference);
            $this->post($reference->getRelationValue('mess'), $actor, $type, $direction, $amount, $date, $account, $description, $reference);
        });
    }

    /**
     * Record a transfer between two accounts without touching income or expense totals.
     */
    public function transfer(Mess $mess, User $actor, MessAccount $from, MessAccount $to, float $amount, string $date, ?string $description = null): void
    {
        DB::transaction(function () use ($mess, $actor, $from, $to, $amount, $date, $description): void {
            $this->post($mess, $actor, MessTransaction::TYPE_TRANSFER_OUT, MessTransaction::DIRECTION_DEBIT, $amount, $date, $from, $description, null, ['counter_account_id' => $to->getKey()]);
            $this->post($mess, $actor, MessTransaction::TYPE_TRANSFER_IN, MessTransaction::DIRECTION_CREDIT, $amount, $date, $to, $description, null, ['counter_account_id' => $from->getKey()]);
        });
    }
}
