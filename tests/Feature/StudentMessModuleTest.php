<?php

use App\Models\BazarRecord;
use App\Models\Mess;
use App\Models\MessAccount;
use App\Models\MessMember;
use App\Models\MessTransaction;
use App\Models\MonthlySettlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\MessService;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function studentPlan(): SubscriptionPlan
{
    return SubscriptionPlan::firstOrCreate(['slug' => 'student-test'], [
        'name' => 'Student Test Plan',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'price' => 0,
        'duration_days' => 30,
        'features' => ['max_messes' => 1],
        'is_active' => true,
    ]);
}

function subscribedStudent(array $attributes = []): User
{
    $user = User::factory()->create(array_merge(['role' => User::ROLE_STUDENT], $attributes));

    UserSubscription::create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => studentPlan()->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    return $user;
}

function createTestMess(User $owner, array $overrides = []): Mess
{
    return app(MessService::class)->create($owner, array_merge([
        'name' => 'Test Mess',
        'address' => 'Dhaka',
        'start_date' => '2026-11-01',
        'billing_month' => '2026-11',
        'monthly_budget' => 10000,
    ], $overrides));
}

function addTestMember(Mess $mess, string $name, string $role = MessMember::ROLE_MEMBER, ?string $email = null): MessMember
{
    return $mess->members()->create([
        'user_id' => null,
        'name' => $name,
        'email' => $email,
        'joining_date' => '2026-11-01',
        'status' => MessMember::STATUS_ACTIVE,
        'role' => $role,
    ]);
}

test('student module requires an active student subscription', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));

    $this->actingAs($user)
        ->get(route('student.subscription.show'))
        ->assertOk();
});

test('a student can activate a subscription and open their workspace list', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
    $plan = studentPlan();

    $this->actingAs($user)
        ->post(route('student.subscription.activate'), ['plan' => $plan->slug])
        ->assertRedirect(route('student.dashboard'));

    expect($user->fresh()->hasActiveStudentSubscription())->toBeTrue();

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.messes.index'));
});

test('an expired student subscription is blocked without affecting other products', function () {
    $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

    UserSubscription::create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => studentPlan()->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now()->subDays(60),
        'ends_at' => now()->subDays(1),
    ]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));

    $family = User::factory()->create(['role' => User::ROLE_FAMILY]);

    $this->actingAs($family)->get(route('dashboard'))->assertOk();
});

test('creating a mess also creates the owner member and default accounts', function () {
    $owner = subscribedStudent();

    $this->actingAs($owner)->post(route('student.messes.store'), [
        'name' => 'Green Mess',
        'address' => 'Mirpur',
        'start_date' => '2026-11-01',
        'billing_month' => '2026-11',
        'monthly_budget' => 12000,
    ])->assertRedirect(route('student.dashboard'));

    $mess = Mess::query()->firstOrFail();

    expect($mess->owner_id)->toBe($owner->getKey())
        ->and($mess->members()->count())->toBe(1)
        ->and($mess->members()->first()->role)->toBe(MessMember::ROLE_OWNER)
        ->and($mess->accounts()->count())->toBe(4);
});

test('a user cannot access another mess', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    $intruder = subscribedStudent();

    $this->actingAs($intruder)
        ->get(route('student.mess.members.index', $mess))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->get(route('student.mess.meals.index', $mess))
        ->assertForbidden();
});

test('member roles control financial and operational access', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    $cash = $mess->accounts()->where('type', MessAccount::TYPE_CASH)->firstOrFail();
    $accountantUser = subscribedStudent();
    $managerUser = subscribedStudent();
    $memberUser = subscribedStudent();

    $memberMembership = $mess->members()->create([
        'user_id' => $memberUser->getKey(),
        'name' => $memberUser->name,
        'email' => $memberUser->email,
        'status' => MessMember::STATUS_ACTIVE,
        'role' => MessMember::ROLE_MEMBER,
        'joining_date' => '2026-11-01',
    ]);

    $mess->members()->create([
        'user_id' => $accountantUser->getKey(),
        'name' => $accountantUser->name,
        'email' => $accountantUser->email,
        'status' => MessMember::STATUS_ACTIVE,
        'role' => MessMember::ROLE_ACCOUNTANT,
        'joining_date' => '2026-11-01',
    ]);
    $mess->members()->create([
        'user_id' => $managerUser->getKey(),
        'name' => $managerUser->name,
        'email' => $managerUser->email,
        'status' => MessMember::STATUS_ACTIVE,
        'role' => MessMember::ROLE_MANAGER,
        'joining_date' => '2026-11-01',
    ]);

    $ownerMember = $mess->members()->where('role', MessMember::ROLE_OWNER)->firstOrFail();

    $depositPayload = [
        'mess_member_id' => $ownerMember->getKey(),
        'deposit_date' => now()->toDateString(),
        'amount' => 1000,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'account_id' => $cash->getKey(),
    ];

    $this->actingAs($accountantUser)
        ->post(route('student.mess.deposits.store', $mess), $depositPayload)
        ->assertRedirect();

    $this->actingAs($managerUser)
        ->post(route('student.mess.deposits.store', $mess), $depositPayload)
        ->assertForbidden();

    $this->actingAs($managerUser)
        ->post(route('student.mess.members.store', $mess), [
            'name' => 'New Member',
            'role' => 'member',
            'joining_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $this->actingAs($accountantUser)
        ->post(route('student.mess.members.store', $mess), [
            'name' => 'Blocked Member',
            'role' => 'member',
        ])
        ->assertForbidden();

    $this->actingAs($memberUser)
        ->post(route('student.mess.deposits.store', $mess), [
            'mess_member_id' => $memberMembership->getKey(),
            'deposit_date' => now()->toDateString(),
            'amount' => 500,
            'type' => 'deposit',
            'payment_method' => 'cash',
            'account_id' => $cash->getKey(),
        ])
        ->assertRedirect();

    $this->actingAs($memberUser)
        ->post(route('student.mess.deposits.store', $mess), [
            'mess_member_id' => $memberMembership->getKey(),
            'deposit_date' => now()->toDateString(),
            'amount' => 500,
            'type' => 'refund',
            'payment_method' => 'cash',
        ])
        ->assertForbidden();

    $this->actingAs($memberUser)
        ->post(route('student.mess.deposits.store', $mess), [
            'mess_member_id' => $ownerMember->getKey(),
            'deposit_date' => now()->toDateString(),
            'amount' => 500,
            'type' => 'deposit',
            'payment_method' => 'cash',
        ])
        ->assertForbidden();

    $this->actingAs($memberUser)
        ->post(route('student.mess.meals.store', $mess), [
            'mess_member_id' => $memberMembership->getKey(),
            'entry_date' => now()->toDateString(),
            'lunch' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($memberUser)
        ->post(route('student.mess.meals.store', $mess), [
            'mess_member_id' => $ownerMember->getKey(),
            'entry_date' => now()->toDateString(),
            'lunch' => 1,
        ])
        ->assertForbidden();
});

test('meal rate and member balances are calculated from database records', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    $tamim = addTestMember($mess, 'Tamim');
    $rahim = addTestMember($mess, 'Rahim');
    $cash = $mess->accounts()->where('type', MessAccount::TYPE_CASH)->firstOrFail();
    $month = '2026-11';

    $mess->mealEntries()->create([
        'mess_member_id' => $tamim->getKey(),
        'entry_date' => '2026-11-05',
        'breakfast' => 10,
        'lunch' => 10,
        'dinner' => 10,
        'created_by' => $owner->getKey(),
    ]);
    $mess->mealEntries()->create([
        'mess_member_id' => $rahim->getKey(),
        'entry_date' => '2026-11-05',
        'breakfast' => 5,
        'lunch' => 10,
        'dinner' => 5,
        'created_by' => $owner->getKey(),
    ]);

    BazarRecord::create([
        'mess_id' => $mess->getKey(),
        'account_id' => $cash->getKey(),
        'purchase_date' => '2026-11-06',
        'category' => 'rice',
        'payment_source' => 'cash',
        'total_amount' => 3000,
        'created_by' => $owner->getKey(),
    ]);

    $mess->deposits()->create([
        'mess_member_id' => $tamim->getKey(),
        'account_id' => $cash->getKey(),
        'deposit_date' => '2026-11-01',
        'amount' => 3000,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'created_by' => $owner->getKey(),
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $rahim->getKey(),
        'account_id' => $cash->getKey(),
        'deposit_date' => '2026-11-01',
        'amount' => 1500,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'created_by' => $owner->getKey(),
    ]);
    $mess->expenses()->create([
        'expense_date' => '2026-11-07',
        'title' => 'Cleaning',
        'amount' => 500,
        'category' => 'other',
        'is_meal_related' => false,
        'allocation_method' => 'equal',
        'created_by' => $owner->getKey(),
    ]);

    $preview = app(SettlementService::class)->preview($mess->fresh(), $month);

    expect($preview['totals']['total_meals'])->toBe(50.0)
        ->and($preview['totals']['total_bazar'])->toBe(3000.0)
        ->and($preview['totals']['meal_rate'])->toBe(60.0)
        ->and($preview['totals']['total_deposits'])->toBe(4500.0)
        ->and($preview['totals']['total_other_expenses'])->toBe(500.0);

    $rows = $preview['members']->keyBy(fn (array $row): string => $row['member']->name);

    expect($rows['Tamim']['meal_cost'])->toBe(1800.0)
        ->and($rows['Tamim']['other_allocated'])->toBe(166.67)
        ->and($rows['Tamim']['balance'])->toBe(1033.33)
        ->and($rows['Rahim']['meal_cost'])->toBe(1200.0)
        ->and($rows['Rahim']['other_allocated'])->toBe(166.66)
        ->and($rows['Rahim']['balance'])->toBe(133.34);

    $this->actingAs($owner)
        ->get(route('student.dashboard'))
        ->assertOk();
});

test('finalizing a month locks financial records and reopening preserves history', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    $member = addTestMember($mess, 'Tamim');
    $cash = $mess->accounts()->where('type', MessAccount::TYPE_CASH)->firstOrFail();
    $month = '2026-11';

    $mess->mealEntries()->create([
        'mess_member_id' => $member->getKey(),
        'entry_date' => '2026-11-05',
        'breakfast' => 1,
        'lunch' => 1,
        'dinner' => 1,
        'created_by' => $owner->getKey(),
    ]);
    $mess->bazarRecords()->create([
        'account_id' => $cash->getKey(),
        'purchase_date' => '2026-11-06',
        'category' => 'rice',
        'payment_source' => 'cash',
        'total_amount' => 300,
        'created_by' => $owner->getKey(),
    ]);

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.finalize', $mess), ['month' => $month])
        ->assertRedirect();

    $settlement = MonthlySettlement::query()->firstOrFail();

    expect($settlement->status)->toBe(MonthlySettlement::STATUS_FINALIZED)
        ->and($settlement->details()->count())->toBe(2)
        ->and($settlement->meal_rate)->toBe('100.0000');

    $this->actingAs($owner)
        ->get(route('student.mess.settlements.show', [$mess, $settlement]))
        ->assertOk();

    $this->actingAs($owner)
        ->post(route('student.mess.meals.store', $mess), [
            'mess_member_id' => $member->getKey(),
            'entry_date' => '2026-11-10',
            'lunch' => 1,
        ])
        ->assertSessionHasErrors('entry_date');

    $this->actingAs($owner)
        ->post(route('student.mess.bazar.store', $mess), [
            'purchase_date' => '2026-11-10',
            'category' => 'fish',
            'payment_source' => 'cash',
            'total_amount' => 500,
        ])
        ->assertSessionHasErrors('purchase_date');

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.reopen', [$mess, $settlement]), [
            'reason' => 'Correcting a missing bazar entry.',
        ])
        ->assertRedirect();

    expect($settlement->fresh()->status)->toBe(MonthlySettlement::STATUS_SUPERSEDED)
        ->and($mess->settlements()->where('month', $month)->count())->toBe(2)
        ->and($mess->settlementForMonth($month)->status)->toBe(MonthlySettlement::STATUS_DRAFT);

    $this->actingAs($owner)
        ->post(route('student.mess.meals.store', $mess), [
            'mess_member_id' => $member->getKey(),
            'entry_date' => '2026-11-10',
            'lunch' => 1,
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.finalize', $mess), ['month' => $month])
        ->assertRedirect();

    expect($mess->settlements()->where('month', $month)->count())->toBe(2)
        ->and($mess->settlements()->where('month', $month)->where('status', MonthlySettlement::STATUS_DRAFT)->count())->toBe(0)
        ->and($mess->settlementForMonth($month)->status)->toBe(MonthlySettlement::STATUS_FINALIZED);
});

test('financial records post ledger entries and transfers never count as income or expense', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    $member = addTestMember($mess, 'Tamim');
    $cash = $mess->accounts()->where('type', MessAccount::TYPE_CASH)->firstOrFail();
    $bank = $mess->accounts()->where('type', MessAccount::TYPE_BANK)->firstOrFail();

    $this->actingAs($owner)->post(route('student.mess.deposits.store', $mess), [
        'mess_member_id' => $member->getKey(),
        'deposit_date' => now()->toDateString(),
        'amount' => 1000,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'account_id' => $cash->getKey(),
    ])->assertRedirect();

    $this->actingAs($owner)->post(route('student.mess.expenses.store', $mess), [
        'title' => 'Cleaning',
        'expense_date' => now()->toDateString(),
        'amount' => 200,
        'category' => 'other',
        'allocation_method' => 'equal',
        'account_id' => $cash->getKey(),
    ])->assertRedirect();

    $this->actingAs($owner)->post(route('student.mess.accounts.transfer', $mess), [
        'from_account_id' => $cash->getKey(),
        'to_account_id' => $bank->getKey(),
        'amount' => 300,
        'transaction_date' => now()->toDateString(),
    ])->assertRedirect();

    expect($cash->fresh()->balance())->toBe(500.0)
        ->and($bank->fresh()->balance())->toBe(300.0)
        ->and($mess->transactions()->where('type', MessTransaction::TYPE_TRANSFER_OUT)->count())->toBe(1)
        ->and($mess->transactions()->where('type', MessTransaction::TYPE_TRANSFER_IN)->count())->toBe(1)
        ->and($mess->auditLogs()->count())->toBeGreaterThan(0);
});

test('invitations create a member only for the invited email', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    $invited = subscribedStudent(['email' => 'invited@example.com']);
    $other = subscribedStudent(['email' => 'other@example.com']);

    $invitation = $mess->invitations()->create([
        'invited_by' => $owner->getKey(),
        'email' => 'invited@example.com',
        'role' => MessMember::ROLE_MEMBER,
        'token' => 'test-token-123',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($other)
        ->post(route('student.invitations.accept', $invitation->token))
        ->assertForbidden();

    $this->actingAs($invited)
        ->get(route('student.invitations.show', $invitation->token))
        ->assertOk();

    $this->actingAs($invited)
        ->post(route('student.invitations.accept', $invitation->token))
        ->assertRedirect(route('student.dashboard'));

    expect($mess->fresh()->members()->where('user_id', $invited->getKey())->exists())->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('reports render and export as csv', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);
    addTestMember($mess, 'Tamim');

    $this->actingAs($owner)
        ->get(route('student.mess.reports.index', $mess))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('student.mess.reports.show', [$mess, 'daily-meal', 'month' => '2026-11']))
        ->assertOk();

    $response = $this->actingAs($owner)
        ->get(route('student.mess.reports.show', [$mess, 'balance', 'month' => '2026-11', 'format' => 'csv']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

test('the dashboard and every module page render for the mess owner', function () {
    $owner = subscribedStudent();
    $mess = createTestMess($owner);

    $routes = [
        route('student.dashboard'),
        route('student.messes.index'),
        route('student.mess.members.index', $mess),
        route('student.mess.meals.index', $mess),
        route('student.mess.bazar.index', $mess),
        route('student.mess.deposits.index', $mess),
        route('student.mess.expenses.index', $mess),
        route('student.mess.accounts.index', $mess),
        route('student.mess.transactions.index', $mess),
        route('student.mess.settlements.index', $mess),
        route('student.mess.reports.index', $mess),
        route('student.mess.settings.edit', $mess),
        route('student.mess.permissions.index', $mess),
        route('student.profile.edit'),
    ];

    foreach ($routes as $url) {
        $this->actingAs($owner)->get($url)->assertOk();
    }
});
