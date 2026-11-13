<?php

use App\Models\Mess;
use App\Models\MessDeposit;
use App\Models\MessMember;
use App\Models\MonthlySettlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\MealRateService;
use App\Services\MessService;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function enginePlan(): SubscriptionPlan
{
    return SubscriptionPlan::firstOrCreate(['slug' => 'engine-student'], [
        'name' => 'Engine Student Plan',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'price' => 0,
        'duration_days' => 30,
        'features' => ['max_messes' => 1],
        'is_active' => true,
    ]);
}

function engineStudent(array $attributes = []): User
{
    $user = User::factory()->create(array_merge(['role' => User::ROLE_STUDENT], $attributes));

    UserSubscription::create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => enginePlan()->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    return $user;
}

function engineMess(User $owner, string $name = 'Engine Mess'): Mess
{
    return app(MessService::class)->create($owner, [
        'name' => $name,
        'start_date' => '2026-11-01',
        'billing_month' => '2026-11',
        'monthly_budget' => 20000,
    ]);
}

function engineMember(Mess $mess, string $name, string $role = MessMember::ROLE_MEMBER): MessMember
{
    return $mess->members()->create([
        'name' => $name,
        'status' => MessMember::STATUS_ACTIVE,
        'role' => $role,
        'joining_date' => '2026-11-01',
    ]);
}

function engineCash(Mess $mess)
{
    return $mess->accounts()->where('type', 'cash')->firstOrFail();
}

function engineMealEntry(Mess $mess, MessMember $member, string $date, float $meals, string $status = 'active')
{
    return $mess->mealEntries()->create([
        'mess_member_id' => $member->getKey(),
        'entry_date' => $date,
        'breakfast' => 0,
        'lunch' => $meals,
        'dinner' => 0,
        'status' => $status,
    ]);
}

test('meal rate uses bazar and meal expenses and never divides by zero', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');
    $b = engineMember($mess, 'B');
    $ownerMember = $mess->members()->where('user_id', $owner->getKey())->firstOrFail();

    engineMealEntry($mess, $a, '2026-11-02', 45);
    engineMealEntry($mess, $b, '2026-11-02', 15);
    engineMealEntry($mess, $ownerMember, '2026-11-02', 4);

    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 3000,
    ]);
    $mess->expenses()->create([
        'title' => 'Gas',
        'expense_date' => '2026-11-03',
        'amount' => 200,
        'category' => 'utility',
        'is_meal_related' => true,
        'allocation_method' => 'equal',
    ]);

    $rate = app(MealRateService::class)->forMonth($mess, '2026-11');

    expect($rate['bazar'])->toBe(3000.0)
        ->and($rate['meal_expenses'])->toBe(200.0)
        ->and($rate['total_meal_cost'])->toBe(3200.0)
        ->and($rate['total_meals'])->toBe(64.0)
        ->and($rate['meal_rate'])->toBe(50.0)
        ->and($rate['member_costs'][$a->getKey()])->toBe(2250.0)
        ->and($rate['member_costs'][$b->getKey()])->toBe(750.0);

    $empty = engineMess(engineStudent(['email' => 'empty@example.com']), 'Empty Mess');
    $zero = app(MealRateService::class)->forMonth($empty, '2026-11');

    expect($zero['meal_rate'])->toBe(0.0)
        ->and($zero['total_meals'])->toBe(0.0)
        ->and($zero['total_meal_cost'])->toBe(0.0);
});

test('meal rate recalculates when expenses or meal entries change and ignores cancelled records', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    engineMealEntry($mess, $a, '2026-11-02', 10);
    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 1000,
    ]);

    $service = app(MealRateService::class);

    expect($service->forMonth($mess, '2026-11')['meal_rate'])->toBe(100.0);

    $mess->expenses()->create([
        'title' => 'Fish',
        'expense_date' => '2026-11-04',
        'amount' => 320,
        'category' => 'food',
        'is_meal_related' => true,
        'allocation_method' => 'equal',
    ]);

    expect($service->forMonth($mess->fresh(), '2026-11')['meal_rate'])->toBe(132.0);

    $entry = $mess->mealEntries()->firstOrFail();
    $entry->update(['lunch' => 20]);

    expect($service->forMonth($mess->fresh(), '2026-11')['meal_rate'])->toBe(66.0);

    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-05',
        'category' => 'cancelled',
        'payment_source' => 'cash',
        'total_amount' => 9999,
        'status' => 'cancelled',
    ]);
    $mess->expenses()->create([
        'title' => 'Cancelled',
        'expense_date' => '2026-11-05',
        'amount' => 9999,
        'is_meal_related' => true,
        'allocation_method' => 'equal',
        'status' => 'cancelled',
    ]);
    engineMealEntry($mess, $a, '2026-11-06', 100, 'cancelled');

    expect($service->forMonth($mess->fresh(), '2026-11')['meal_rate'])->toBe(66.0)
        ->and($service->forMonth($mess->fresh(), '2026-11')['total_meals'])->toBe(20.0);
});

test('settlement computes opening balance deposits refunds adjustments personal and shared costs', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');
    $b = engineMember($mess, 'B');
    $cash = engineCash($mess);

    $previous = MonthlySettlement::create([
        'mess_id' => $mess->getKey(),
        'month' => '2026-10',
        'version' => 1,
        'status' => MonthlySettlement::STATUS_FINALIZED,
        'finalized_at' => now(),
    ]);
    $previous->details()->create(['mess_member_id' => $a->getKey(), 'balance' => 500, 'opening_balance' => 0]);
    $previous->details()->create(['mess_member_id' => $b->getKey(), 'balance' => -100, 'opening_balance' => 0]);

    engineMealEntry($mess, $a, '2026-11-02', 45);
    engineMealEntry($mess, $b, '2026-11-02', 30);

    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 3750,
    ]);
    $mess->expenses()->create([
        'title' => 'Shared',
        'expense_date' => '2026-11-03',
        'amount' => 250,
        'category' => 'other',
        'is_meal_related' => false,
        'allocation_method' => 'meals',
    ]);
    $mess->expenses()->create([
        'title' => 'Personal',
        'expense_date' => '2026-11-03',
        'amount' => 300,
        'category' => 'personal',
        'is_meal_related' => false,
        'is_personal' => true,
        'mess_member_id' => $a->getKey(),
        'allocation_method' => 'equal',
    ]);

    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'account_id' => $cash->getKey(),
        'deposit_date' => '2026-11-01',
        'amount' => 5000,
        'type' => 'deposit',
        'payment_method' => 'cash',
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'account_id' => $cash->getKey(),
        'deposit_date' => '2026-11-02',
        'amount' => 100,
        'type' => 'refund',
        'payment_method' => 'cash',
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'account_id' => $cash->getKey(),
        'deposit_date' => '2026-11-02',
        'amount' => 50,
        'type' => 'adjustment',
        'payment_method' => 'cash',
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'account_id' => $cash->getKey(),
        'deposit_date' => '2026-11-02',
        'amount' => 9999,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'status' => 'cancelled',
    ]);

    $preview = app(SettlementService::class)->preview($mess, '2026-11');
    $rows = $preview['members']->keyBy(fn (array $row): string => $row['member']->name);

    expect($preview['totals']['meal_rate'])->toBe(50.0)
        ->and($preview['totals']['total_meals'])->toBe(75.0)
        ->and($preview['totals']['total_shared_expenses'])->toBe(250.0)
        ->and($preview['totals']['total_personal_expenses'])->toBe(300.0)
        ->and($preview['totals']['total_deposits'])->toBe(5000.0)
        ->and($preview['totals']['total_refunds'])->toBe(100.0)
        ->and($preview['totals']['total_adjustments'])->toBe(50.0)
        ->and($rows['A']['opening_balance'])->toBe(500.0)
        ->and($rows['A']['meal_cost'])->toBe(2250.0)
        ->and($rows['A']['shared_allocated'])->toBe(150.0)
        ->and($rows['A']['personal_expenses'])->toBe(300.0)
        ->and($rows['A']['balance'])->toBe(2950.0)
        ->and($rows['A']['status'])->toBe('receivable')
        ->and($rows['B']['opening_balance'])->toBe(-100.0)
        ->and($rows['B']['meal_cost'])->toBe(1500.0)
        ->and($rows['B']['shared_allocated'])->toBe(100.0)
        ->and($rows['B']['balance'])->toBe(-1700.0)
        ->and($rows['B']['status'])->toBe('due')
        ->and($preview['totals']['total_receivable'])->toBe(2950.0)
        ->and($preview['totals']['total_outstanding_dues'])->toBe(1700.0);

    $this->actingAs($owner)->get(route('student.dashboard'))->assertOk();
    $this->actingAs($owner)->get(route('student.mess.settlements.index', $mess))->assertOk();
});

test('finalizing stores immutable snapshots prevents duplicates and carries balances forward', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    engineMealEntry($mess, $a, '2026-11-02', 10);
    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 1000,
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'deposit_date' => '2026-11-01',
        'amount' => 2000,
        'type' => 'deposit',
        'payment_method' => 'cash',
    ]);

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.finalize', $mess), ['month' => '2026-11'])
        ->assertRedirect();

    $settlement = MonthlySettlement::query()->firstOrFail();
    $detail = $settlement->details()->where('mess_member_id', $a->getKey())->firstOrFail();

    expect($settlement->status)->toBe(MonthlySettlement::STATUS_FINALIZED)
        ->and((float) $settlement->total_meal_cost)->toBe(1000.0)
        ->and((float) $detail->balance)->toBe(1000.0);

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.finalize', $mess), ['month' => '2026-11'])
        ->assertSessionHasErrors('month');

    expect($mess->settlements()->where('month', '2026-11')->where('status', 'finalized')->count())->toBe(1);

    $december = app(SettlementService::class)->preview($mess, '2026-12');
    $decRow = $december['members']->first(fn (array $row): bool => $row['member']->is($a));

    expect($decRow['opening_balance'])->toBe(1000.0);

    $this->actingAs($owner)
        ->post(route('student.mess.meals.store', $mess), [
            'mess_member_id' => $a->getKey(),
            'entry_date' => '2026-11-20',
            'lunch' => 1,
        ])
        ->assertSessionHasErrors('entry_date');
});

test('reopening preserves the finalized snapshot and creates auditable corrections', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    engineMealEntry($mess, $a, '2026-11-02', 10);
    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 1000,
    ]);

    $settlement = app(SettlementService::class)->finalize($mess, '2026-11', $owner);
    $originalBalance = (float) $settlement->details()->where('mess_member_id', $a->getKey())->value('balance');

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.reopen', [$mess, $settlement]), [
            'reason' => 'Adding a missed deposit as an auditable correction.',
        ])
        ->assertRedirect();

    expect($settlement->fresh()->status)->toBe(MonthlySettlement::STATUS_SUPERSEDED)
        ->and((float) $settlement->details()->where('mess_member_id', $a->getKey())->value('balance'))->toBe($originalBalance);

    $this->actingAs($owner)->post(route('student.mess.deposits.store', $mess), [
        'mess_member_id' => $a->getKey(),
        'deposit_date' => '2026-11-15',
        'amount' => 250,
        'type' => 'adjustment',
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect($mess->auditLogs()->where('action', 'settlement.reopened')->exists())->toBeTrue()
        ->and($mess->auditLogs()->where('action', 'deposit.created')->exists())->toBeTrue();

    $this->actingAs($owner)
        ->post(route('student.mess.settlements.finalize', $mess), ['month' => '2026-11'])
        ->assertRedirect();

    $latest = $mess->settlements()->where('month', '2026-11')->latest('version')->firstOrFail();

    expect($latest->status)->toBe(MonthlySettlement::STATUS_FINALIZED)
        ->and((float) $latest->details()->where('mess_member_id', $a->getKey())->value('balance'))->toBe($originalBalance + 250.0);
});

test('mess A cannot read or mutate mess B records through ids or url tampering', function () {
    $ownerA = engineStudent(['email' => 'owner-a@example.com']);
    $ownerB = engineStudent(['email' => 'owner-b@example.com']);
    $messA = engineMess($ownerA, 'Mess A');
    $messB = engineMess($ownerB, 'Mess B');
    $memberA = engineMember($messA, 'Member A');
    $memberB = engineMember($messB, 'Member B');
    $cashA = engineCash($messA);
    $cashB = engineCash($messB);

    engineMealEntry($messA, $memberA, '2026-11-02', 2);
    $entryB = engineMealEntry($messB, $memberB, '2026-11-02', 3);
    $bazarB = $messB->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 500,
    ]);
    $depositB = $messB->deposits()->create([
        'mess_member_id' => $memberB->getKey(),
        'deposit_date' => '2026-11-01',
        'amount' => 900,
        'type' => 'deposit',
        'payment_method' => 'cash',
    ]);
    $settlementB = app(SettlementService::class)->finalize($messB, '2026-11', $ownerB);

    $this->actingAs($ownerA)->get(route('student.mess.members.index', $messB))->assertForbidden();
    $this->actingAs($ownerA)->get(route('student.mess.meals.index', $messB))->assertForbidden();
    $this->actingAs($ownerA)->get(route('student.mess.deposits.index', $messB))->assertForbidden();
    $this->actingAs($ownerA)->get(route('student.mess.settlements.index', $messB))->assertForbidden();
    $this->actingAs($ownerA)->get(route('student.mess.settlements.show', [$messB, $settlementB]))->assertForbidden();

    $this->actingAs($ownerA)
        ->put(route('student.mess.meals.update', [$messA, $entryB]), [
            'mess_member_id' => $memberA->getKey(),
            'entry_date' => '2026-11-04',
            'lunch' => 5,
        ])
        ->assertNotFound();

    $this->actingAs($ownerA)
        ->put(route('student.mess.bazar.update', [$messA, $bazarB]), [
            'purchase_date' => '2026-11-04',
            'payment_source' => 'cash',
            'total_amount' => 1,
        ])
        ->assertNotFound();

    $this->actingAs($ownerA)
        ->delete(route('student.mess.deposits.destroy', [$messA, $depositB]))
        ->assertNotFound();

    $this->actingAs($ownerA)
        ->post(route('student.mess.deposits.store', $messA), [
            'mess_member_id' => $memberB->getKey(),
            'deposit_date' => '2026-11-05',
            'amount' => 100,
            'type' => 'deposit',
            'payment_method' => 'cash',
            'account_id' => $cashB->getKey(),
        ])
        ->assertSessionHasErrors(['mess_member_id', 'account_id']);

    expect((float) $entryB->fresh()->lunch)->toBe(3.0)
        ->and((float) $bazarB->fresh()->total_amount)->toBe(500.0)
        ->and($depositB->fresh())->not->toBeNull()
        ->and((float) $cashA->fresh()->balance())->toBe(0.0);
});

test('unauthorized roles cannot perform financial operations and expired subscriptions are blocked', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $memberUser = engineStudent(['email' => 'plain-member@example.com']);
    $member = $mess->members()->create([
        'user_id' => $memberUser->getKey(),
        'name' => $memberUser->name,
        'email' => $memberUser->email,
        'status' => MessMember::STATUS_ACTIVE,
        'role' => MessMember::ROLE_MEMBER,
        'joining_date' => '2026-11-01',
    ]);

    $this->actingAs($memberUser)->post(route('student.mess.expenses.store', $mess), [
        'title' => 'Unauthorized',
        'expense_date' => '2026-11-03',
        'amount' => 100,
        'allocation_method' => 'equal',
    ])->assertForbidden();

    $this->actingAs($memberUser)->post(route('student.mess.bazar.store', $mess), [
        'purchase_date' => '2026-11-03',
        'payment_source' => 'cash',
        'total_amount' => 100,
    ])->assertForbidden();

    $settlement = app(SettlementService::class)->finalize($mess, '2026-11', $owner);

    $this->actingAs($memberUser)->post(route('student.mess.settlements.reopen', [$mess, $settlement]), [
        'reason' => 'Not allowed',
    ])->assertForbidden();

    $expiredUser = User::factory()->create(['role' => User::ROLE_STUDENT]);
    UserSubscription::create([
        'user_id' => $expiredUser->getKey(),
        'subscription_plan_id' => enginePlan()->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now()->subDays(60),
        'ends_at' => now()->subDay(),
    ]);

    $this->actingAs($expiredUser)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));

    $this->actingAs($expiredUser)->get(route('dashboard'))->assertOk()->assertSee(__('subscription.onboarding.title'));

    expect($expiredUser->hasActiveStudentSubscription())->toBeFalse()
        ->and($member->isActive())->toBeTrue();
});

test('member statements, dues, and receivable reports are generated from the snapshot engine', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    engineMealEntry($mess, $a, '2026-11-02', 10);
    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 500,
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'deposit_date' => '2026-11-01',
        'amount' => 1000,
        'type' => 'deposit',
        'payment_method' => 'cash',
    ]);

    $this->actingAs($owner)
        ->get(route('student.mess.reports.show', [$mess, 'member-statement', 'month' => '2026-11', 'member' => $a->getKey()]))
        ->assertOk()
        ->assertSee('A');

    $this->actingAs($owner)
        ->get(route('student.mess.reports.show', [$mess, 'receivable', 'month' => '2026-11']))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('student.mess.reports.show', [$mess, 'outstanding-dues', 'month' => '2026-11']))
        ->assertOk();

    $csv = $this->actingAs($owner)
        ->get(route('student.mess.reports.show', [$mess, 'outstanding-dues', 'month' => '2026-11', 'format' => 'csv']));

    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv');
});

test('fractional meal counts are supported while empty entries are rejected', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    $this->actingAs($owner)->post(route('student.mess.meals.store', $mess), [
        'mess_member_id' => $a->getKey(),
        'entry_date' => '2026-11-02',
        'breakfast' => 0,
        'lunch' => 0,
        'dinner' => 0,
    ])->assertSessionHasErrors('breakfast');

    $this->actingAs($owner)->post(route('student.mess.meals.store', $mess), [
        'mess_member_id' => $a->getKey(),
        'entry_date' => '2026-11-02',
        'breakfast' => 0.5,
        'lunch' => 1.25,
        'dinner' => 0.25,
    ])->assertSessionHasNoErrors();

    $entry = $mess->mealEntries()->firstOrFail();

    expect((float) $entry->total_meals)->toBe(2.0)
        ->and((float) $entry->breakfast)->toBe(0.5);

    $mess->bazarRecords()->create([
        'purchase_date' => '2026-11-03',
        'category' => 'bazar',
        'payment_source' => 'cash',
        'total_amount' => 100,
    ]);

    expect(app(MealRateService::class)->forMonth($mess, '2026-11')['meal_rate'])->toBe(50.0);
});

test('personal expenses require a member and are persisted through the expense form', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    $this->actingAs($owner)->post(route('student.mess.expenses.store', $mess), [
        'title' => 'Personal charge',
        'expense_date' => '2026-11-04',
        'amount' => 75,
        'allocation_method' => 'equal',
        'is_personal' => 1,
    ])->assertSessionHasErrors('mess_member_id');

    $this->actingAs($owner)->post(route('student.mess.expenses.store', $mess), [
        'title' => 'Personal charge',
        'expense_date' => '2026-11-04',
        'amount' => 75,
        'allocation_method' => 'equal',
        'is_personal' => 1,
        'mess_member_id' => $a->getKey(),
    ])->assertSessionHasNoErrors();

    $expense = $mess->expenses()->firstOrFail();

    expect($expense->is_personal)->toBeTrue()
        ->and($expense->mess_member_id)->toBe($a->getKey());
});

test('duplicate deposits for the same member date amount and type are rejected', function () {
    $owner = engineStudent();
    $mess = engineMess($owner);
    $a = engineMember($mess, 'A');

    $payload = [
        'mess_member_id' => $a->getKey(),
        'deposit_date' => '2026-11-05',
        'amount' => 300,
        'type' => 'deposit',
        'payment_method' => 'cash',
    ];

    $this->actingAs($owner)->post(route('student.mess.deposits.store', $mess), $payload)->assertRedirect();
    expect($mess->deposits()->where('status', MessDeposit::STATUS_ACTIVE)->count())->toBe(1);
    $this->actingAs($owner)->post(route('student.mess.deposits.store', $mess), $payload)->assertSessionHasErrors('amount');

    expect($mess->deposits()->where('status', MessDeposit::STATUS_ACTIVE)->count())->toBe(1);
});
