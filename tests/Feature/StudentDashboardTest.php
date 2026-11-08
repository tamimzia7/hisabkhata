<?php

use App\Models\Mess;
use App\Models\MessMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\DashboardService;
use App\Services\MessService;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashPlan(): SubscriptionPlan
{
    return SubscriptionPlan::firstOrCreate(['slug' => 'dashboard-student'], [
        'name' => 'Dashboard Student Plan',
        'product' => SubscriptionPlan::PRODUCT_STUDENT,
        'price' => 0,
        'duration_days' => 30,
        'features' => ['max_messes' => 1],
        'is_active' => true,
    ]);
}

function dashStudent(array $attributes = []): User
{
    $user = User::factory()->create(array_merge(['role' => User::ROLE_STUDENT], $attributes));

    UserSubscription::create([
        'user_id' => $user->getKey(),
        'subscription_plan_id' => dashPlan()->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    return $user;
}

function dashMess(User $owner, string $name = 'Dashboard Mess'): Mess
{
    return app(MessService::class)->create($owner, [
        'name' => $name,
        'address' => 'Dhanmondi, Dhaka',
        'start_date' => now()->startOfMonth()->toDateString(),
        'billing_month' => now()->format('Y-m'),
        'monthly_budget' => 25000,
    ]);
}

function dashMember(Mess $mess, string $name, string $role = MessMember::ROLE_MEMBER, ?User $user = null, string $status = MessMember::STATUS_ACTIVE): MessMember
{
    return $mess->members()->create([
        'user_id' => $user?->getKey(),
        'name' => $name,
        'email' => $user?->email,
        'status' => $status,
        'role' => $role,
        'joining_date' => now()->startOfMonth()->toDateString(),
    ]);
}

function dashPopulate(Mess $mess): void
{
    $month = CarbonImmutable::now();
    $previous = $month->subMonthNoOverflow();
    $ownerMember = $mess->members()->where('role', MessMember::ROLE_OWNER)->firstOrFail();
    $a = dashMember($mess, 'Ayesha Rahman');
    $b = dashMember($mess, 'Bashir Ahmed');

    foreach ([
        [$a, $month->startOfMonth()->addDays(2), 30],
        [$b, $month->startOfMonth()->addDays(3), 10],
    ] as [$member, $date, $meals]) {
        $mess->mealEntries()->create([
            'mess_member_id' => $member->getKey(),
            'entry_date' => $date->toDateString(),
            'breakfast' => $meals / 2,
            'lunch' => $meals / 2,
            'dinner' => 0,
            'created_by' => auth()->id() ?? $mess->owner_id,
        ]);
    }

    $mess->mealEntries()->create([
        'mess_member_id' => $ownerMember->getKey(),
        'entry_date' => $previous->startOfMonth()->addDays(2)->toDateString(),
        'breakfast' => 10,
        'lunch' => 10,
        'dinner' => 0,
        'created_by' => $mess->owner_id,
    ]);

    $mess->bazarRecords()->create([
        'purchase_date' => $month->startOfMonth()->addDays(2)->toDateString(),
        'category' => 'market',
        'payment_source' => 'cash',
        'total_amount' => 3000,
        'created_by' => $mess->owner_id,
    ]);
    $mess->bazarRecords()->create([
        'purchase_date' => $previous->startOfMonth()->addDays(2)->toDateString(),
        'category' => 'market',
        'payment_source' => 'cash',
        'total_amount' => 1600,
        'created_by' => $mess->owner_id,
    ]);
    $mess->expenses()->create([
        'title' => 'Electricity bill',
        'expense_date' => $month->startOfMonth()->addDays(4)->toDateString(),
        'amount' => 200,
        'category' => 'utility',
        'is_meal_related' => true,
        'allocation_method' => 'equal',
        'created_by' => $mess->owner_id,
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $a->getKey(),
        'deposit_date' => $month->startOfMonth()->toDateString(),
        'amount' => 5000,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'created_by' => $mess->owner_id,
    ]);
    $mess->deposits()->create([
        'mess_member_id' => $ownerMember->getKey(),
        'deposit_date' => $previous->startOfMonth()->toDateString(),
        'amount' => 1000,
        'type' => 'deposit',
        'payment_method' => 'cash',
        'created_by' => $mess->owner_id,
    ]);
}

test('owner dashboard loads real kpis, charts and member balances', function () {
    $owner = dashStudent();
    $mess = dashMess($owner);
    dashPopulate($mess);
    $ownerMember = $mess->members()->where('user_id', $owner->getKey())->firstOrFail();

    $summary = app(DashboardService::class)->summary($mess, now()->format('Y-m'), $ownerMember);

    expect($summary['kpis']['bazar']['value'])->toBe(3000.0)
        ->and($summary['kpis']['bazar']['previous'])->toBe(1600.0)
        ->and($summary['kpis']['bazar']['change'])->toBe(87.5)
        ->and($summary['kpis']['meals']['value'])->toBe(40.0)
        ->and($summary['kpis']['meals']['previous'])->toBe(20.0)
        ->and($summary['kpis']['meals']['change'])->toBe(100.0)
        ->and($summary['kpis']['meal_rate']['value'])->toBe(80.0)
        ->and($summary['kpis']['meal_rate']['previous'])->toBe(80.0)
        ->and($summary['kpis']['meal_rate']['change'])->toBe(0.0)
        ->and($summary['kpis']['members']['active'])->toBe(3)
        ->and($summary['kpis']['dues']['value'])->toBe(800.0)
        ->and($summary['kpis']['dues']['count'])->toBe(1)
        ->and($summary['charts']['breakdown']['values'])->toBe([3000.0, 0.0, 200.0, 0.0])
        ->and($summary['charts']['breakdown']['total'])->toBe(3200.0)
        ->and($summary['settlement']['status'])->toBe('pending');

    $ayesha = $summary['members']->first(fn (array $row): bool => $row['member']->name === 'Ayesha Rahman');
    $bashir = $summary['members']->first(fn (array $row): bool => $row['member']->name === 'Bashir Ahmed');

    expect($ayesha['meals'])->toBe(30.0)
        ->and($ayesha['meal_cost'])->toBe(2400.0)
        ->and($ayesha['balance'])->toBe(2600.0)
        ->and($ayesha['status'])->toBe('receivable')
        ->and($bashir['balance'])->toBe(-800.0)
        ->and($bashir['status'])->toBe('due');

    $this->actingAs($owner)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(__('student.dashboard.kpis.total_balance'))
        ->assertSee(__('student.dashboard.kpis.monthly_bazar'))
        ->assertSee(__('student.dashboard.kpis.meal_rate'))
        ->assertSee(__('student.dashboard.expense_breakdown'))
        ->assertSee(__('student.dashboard.member_overview'))
        ->assertSee(__('student.dashboard.quick_actions'))
        ->assertSee(__('student.dashboard.next_settlement'))
        ->assertSee('Ayesha Rahman')
        ->assertSee('Bashir Ahmed')
        ->assertSee(__('student.nav.mess_settings'))
        ->assertSee(__('student.nav.member_permissions'))
        ->assertSee('Breadcrumb')
        ->assertSee(__('student.nav.breadcrumb_root'))
        ->assertSee('lg:grid-cols-3', false)
        ->assertSee('xl:grid-cols-6', false);
});

test('month selector changes the dashboard calculations without leaving the page', function () {
    $owner = dashStudent();
    $mess = dashMess($owner);
    dashPopulate($mess);
    $previous = now()->subMonthNoOverflow()->format('Y-m');

    $summary = app(DashboardService::class)->summary($mess, $previous);
    expect($summary['month'])->toBe($previous)
        ->and($summary['kpis']['bazar']['value'])->toBe(1600.0);

    $this->actingAs($owner)
        ->get(route('student.dashboard', ['month' => $previous]))
        ->assertOk()
        ->assertSee('value="'.$previous.'"', false)
        ->assertSee(now()->subMonthNoOverflow()->format('F Y'));
});

test('manager, accountant and member dashboards expose role-appropriate actions', function () {
    $owner = dashStudent(['email' => 'owner-dash@example.com']);
    $mess = dashMess($owner);

    $managerUser = dashStudent(['email' => 'manager-dash@example.com']);
    dashMember($mess, 'Manager Person', MessMember::ROLE_MANAGER, $managerUser);

    $accountantUser = dashStudent(['email' => 'accountant-dash@example.com']);
    dashMember($mess, 'Accountant Person', MessMember::ROLE_ACCOUNTANT, $accountantUser);

    $memberUser = dashStudent(['email' => 'member-dash@example.com']);
    dashMember($mess, 'Plain Member', MessMember::ROLE_MEMBER, $memberUser);

    $this->actingAs($managerUser)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(__('student.dashboard.actions.add_bazar'))
        ->assertDontSee(__('student.nav.mess_settings'))
        ->assertDontSee(__('student.nav.member_permissions'));

    $this->actingAs($accountantUser)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(__('student.nav.accounts'))
        ->assertSee(__('student.nav.transactions'))
        ->assertDontSee(__('student.dashboard.actions.add_bazar'));

    $this->actingAs($memberUser)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(__('student.dashboard.actions.add_meal'))
        ->assertSee(__('student.dashboard.actions.add_deposit'))
        ->assertSee(__('student.dashboard.actions.view_reports'))
        ->assertDontSee(__('student.dashboard.actions.add_bazar'))
        ->assertDontSee(__('student.nav.mess_settings'))
        ->assertDontSee(__('student.nav.accounts'))
        ->assertSee('Plain Member')
        ->assertDontSee('Manager Person');
});

test('empty mess and zero meal dashboards are safe', function () {
    $owner = dashStudent();
    $mess = dashMess($owner);
    $ownerMember = $mess->members()->where('user_id', $owner->getKey())->firstOrFail();

    $summary = app(DashboardService::class)->summary($mess, now()->format('Y-m'), $ownerMember);

    expect($summary['kpis']['bazar']['value'])->toBe(0.0)
        ->and($summary['kpis']['meals']['value'])->toBe(0.0)
        ->and($summary['kpis']['meal_rate']['value'])->toBe(0.0)
        ->and($summary['charts']['breakdown']['total'])->toBe(0.0)
        ->and($summary['activities'])->toHaveCount(0);

    $this->actingAs($owner)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(__('student.dashboard.no_activity'));

    $mess->bazarRecords()->create([
        'purchase_date' => now()->toDateString(),
        'category' => 'market',
        'payment_source' => 'cash',
        'total_amount' => 1000,
    ]);

    $zeroMeals = app(DashboardService::class)->summary($mess->fresh(), now()->format('Y-m'), $ownerMember);

    expect($zeroMeals['kpis']['meal_rate']['value'])->toBe(0.0)
        ->and($zeroMeals['kpis']['meals']['value'])->toBe(0.0);
});

test('member overview search and pagination work', function () {
    $owner = dashStudent();
    $mess = dashMess($owner);

    foreach (range(1, 12) as $index) {
        dashMember($mess, 'Member '.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
    }

    $this->actingAs($owner)->get(route('student.dashboard', ['q' => 'Member 11']))
        ->assertOk()
        ->assertSee('Member 11')
        ->assertDontSee('Member 10');

    $this->actingAs($owner)->get(route('student.dashboard', ['page' => 2]))
        ->assertOk()
        ->assertSee('Member 12');
});

test('finalized settlement is reflected in the dashboard settlement panel', function () {
    $owner = dashStudent();
    $mess = dashMess($owner);
    dashPopulate($mess);

    app(SettlementService::class)->finalize($mess, now()->format('Y-m'), $owner);

    $summary = app(DashboardService::class)->summary($mess->fresh(), now()->format('Y-m'));

    expect($summary['settlement']['status'])->toBe('finalized')
        ->and($summary['settlement']['is_finalized'])->toBeTrue();

    $this->actingAs($owner)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee(__('student.dashboard.settlement_statuses.finalized'))
        ->assertSee(__('student.dashboard.finalized_on', ['date' => $summary['settlement']['record']->finalized_at->format('d M Y H:i')]));
});

test('dashboard is isolated and rejects expired student subscriptions while other products stay active', function () {
    $owner = dashStudent(['email' => 'iso-owner@example.com']);
    $messA = dashMess($owner, 'Isolated Mess A');
    $otherOwner = dashStudent(['email' => 'iso-other@example.com']);
    $messB = dashMess($otherOwner, 'Isolated Mess B');

    $memberUser = dashStudent(['email' => 'iso-member@example.com']);
    dashMember($messA, 'Isolated Member', MessMember::ROLE_MEMBER, $memberUser);

    $this->actingAs($memberUser)->withSession(['student_mess_id' => $messB->getKey()])
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Isolated Member')
        ->assertDontSee('Isolated Mess B');

    $noMessUser = dashStudent(['email' => 'no-mess@example.com']);
    $this->actingAs($noMessUser)->get(route('student.dashboard'))
        ->assertRedirect(route('student.messes.index'));

    $expired = User::factory()->create(['role' => User::ROLE_STUDENT]);
    UserSubscription::create([
        'user_id' => $expired->getKey(),
        'subscription_plan_id' => dashPlan()->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now()->subDays(60),
        'ends_at' => now()->subDay(),
    ]);

    $this->actingAs($expired)->get(route('student.dashboard'))
        ->assertRedirect(route('student.subscription.show'));

    UserSubscription::create([
        'user_id' => $expired->getKey(),
        'subscription_plan_id' => SubscriptionPlan::create([
            'slug' => 'dashboard-family',
            'name' => 'Family Plan',
            'product' => SubscriptionPlan::PRODUCT_FAMILY,
            'price' => 0,
            'duration_days' => 30,
            'is_active' => true,
        ])->getKey(),
        'status' => UserSubscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
    ]);

    $this->actingAs($expired)->get(route('dashboard'))->assertOk();
    expect($expired->hasActiveSubscription(SubscriptionPlan::PRODUCT_FAMILY))->toBeTrue()
        ->and($expired->hasActiveStudentSubscription())->toBeFalse();
});
