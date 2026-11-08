<?php

namespace App\Services;

use App\Models\Mess;
use App\Models\MessAccount;
use App\Models\MessMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessService
{
    /**
     * Create a mess with its owner member and default money accounts.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $owner, array $attributes): Mess
    {
        return DB::transaction(function () use ($owner, $attributes): Mess {
            $mess = Mess::create([
                ...$attributes,
                'owner_id' => $owner->getKey(),
                'billing_month' => $attributes['billing_month'] ?? now()->format('Y-m'),
            ]);

            MessMember::create([
                'mess_id' => $mess->getKey(),
                'user_id' => $owner->getKey(),
                'name' => $owner->name,
                'email' => $owner->email,
                'phone' => $owner->phone,
                'joining_date' => $mess->start_date?->toDateString() ?? now()->toDateString(),
                'status' => MessMember::STATUS_ACTIVE,
                'role' => MessMember::ROLE_OWNER,
            ]);

            foreach ($this->defaultAccounts() as $account) {
                MessAccount::create([
                    'mess_id' => $mess->getKey(),
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'opening_balance' => 0,
                    'is_active' => true,
                ]);
            }

            return $mess;
        });
    }

    /**
     * Default accounts created for every new mess.
     *
     * @return list<array{name: string, type: string}>
     */
    public function defaultAccounts(): array
    {
        return [
            ['name' => __('student.accounts.types.cash'), 'type' => MessAccount::TYPE_CASH],
            ['name' => __('student.accounts.types.bank'), 'type' => MessAccount::TYPE_BANK],
            ['name' => __('student.accounts.types.bkash'), 'type' => MessAccount::TYPE_BKASH],
            ['name' => __('student.accounts.types.nagad'), 'type' => MessAccount::TYPE_NAGAD],
        ];
    }

    /**
     * The mess the user should land on, preferring the session selection.
     */
    public function activeMessFor(User $user, ?int $preferredId = null): ?Mess
    {
        if ($preferredId !== null) {
            $member = $user->messMemberships()->where('mess_id', $preferredId)->first();

            if ($member) {
                return $member->mess;
            }
        }

        return $user->messMemberships()
            ->with('mess')
            ->orderByDesc('id')
            ->first()?->mess;
    }
}
