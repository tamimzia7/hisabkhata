@extends('layouts.student')

@section('title', __('student.dashboard.title'))
@section('breadcrumb', __('student.nav.dashboard'))
@section('heading', __('student.dashboard.title'))

@php
    $kpis = $summary['kpis'];
    $charts = $summary['charts'];
    $settlement = $summary['settlement'];
    $canManageBazar = $member->allows('manage_bazar');
    $canViewReports = $member->allows('view_reports');
    $monthLabel = $summary['month_label'];
    $activityIcons = [
        'deposit' => 'wallet',
        'bazar' => 'shopping-bag',
        'meal' => 'utensils',
        'meal_updated' => 'pencil-line',
        'expense' => 'receipt',
        'settlement' => 'calendar-check',
        'member' => 'user-plus',
    ];
    $changePill = function (?float $change): string {
        if ($change === null) {
            return 'bg-white/5 text-gray-400';
        }

        return $change >= 0 ? 'bg-emerald-500/15 text-emerald-300' : 'bg-orange-500/15 text-orange-300';
    };
@endphp

@section('content')
<div class="space-y-4 sm:space-y-5">
    {{-- Mess header --}}
    <section class="glass-strong rounded-2xl p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-3 sm:gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-300 via-emerald-500 to-emerald-700 text-lg font-black text-[#04120B] shadow-lg shadow-emerald-500/20 sm:h-14 sm:w-14">
                {{ mb_strtoupper(mb_substr($mess->name, 0, 1)) }}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="truncate text-lg font-bold font-display font-bn text-white sm:text-xl">{{ $mess->name }}</h2>
                    <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300 font-bn">
                        {{ $subscription ? __('student.subscription.active') : __('student.subscription.inactive') }}
                    </span>
                    <span class="rounded-full bg-white/5 px-2.5 py-0.5 text-[10px] font-bold text-gray-300 font-bn">
                        {{ __('student.members.roles.'.$member->role) }}
                    </span>
                </div>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-400 font-bn">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                        {{ $mess->address ?: __('student.mess.no_address') }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                        {{ $monthLabel }}
                    </span>
                    @if($subscription?->ends_at)
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="badge-check" class="h-3.5 w-3.5"></i>
                            {{ __('student.subscription.expires') }} {{ $subscription->ends_at->format('d M Y') }}
                        </span>
                    @endif
                </p>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <form method="GET" action="{{ route('student.dashboard') }}" class="flex items-center gap-2">
                    <label for="dashboard-month" class="sr-only">{{ __('student.dashboard.month') }}</label>
                    <input id="dashboard-month" type="month" name="month" value="{{ $month }}" onchange="this.form.submit()"
                           class="field w-[150px] py-2 text-xs">
                </form>

                <div class="relative" x-data="{ wsOpen: false }" @click.outside="wsOpen = false" @keydown.escape.window="wsOpen = false">
                    <button @click="wsOpen = !wsOpen" class="btn-ghost flex items-center gap-2 rounded-lg px-3 py-2 text-[11px] text-gray-200 font-bn">
                        <i data-lucide="chevrons-up-down" class="h-3.5 w-3.5"></i>
                        {{ __('student.nav.switch_mess') }}
                    </button>
                    <div x-show="wsOpen" x-cloak x-transition.origin.top.right class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-white/10 bg-[#081510] shadow-2xl">
                        @foreach($memberships as $membership)
                            <form method="POST" action="{{ route('student.messes.switch', $membership->mess) }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-xs transition hover:bg-white/5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-[10px] font-bold text-emerald-300">{{ mb_strtoupper(mb_substr($membership->mess->name, 0, 1)) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-bold text-white font-bn">{{ $membership->mess->name }}</span>
                                        <span class="block text-[10px] text-gray-500 font-bn">{{ __('student.members.roles.'.$membership->role) }}</span>
                                    </span>
                                    @if($membership->mess_id === $mess->getKey())
                                        <i data-lucide="check" class="h-3.5 w-3.5 text-emerald-400"></i>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <section class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-3 xl:grid-cols-6">
        @php
            $cards = [
                [
                    'icon' => 'wallet',
                    'label' => __('student.dashboard.kpis.total_balance'),
                    'value' => '৳'.localize_money($kpis['balance']['value']),
                    'previous' => '৳'.localize_money($kpis['balance']['previous']),
                    'change' => $kpis['balance']['change'],
                    'tone' => 'text-emerald-300',
                    'iconTone' => 'bg-emerald-500/15 text-emerald-300',
                ],
                [
                    'icon' => 'shopping-cart',
                    'label' => __('student.dashboard.kpis.monthly_bazar'),
                    'value' => '৳'.localize_money($kpis['bazar']['value']),
                    'previous' => '৳'.localize_money($kpis['bazar']['previous']),
                    'change' => $kpis['bazar']['change'],
                    'tone' => 'text-orange-300',
                    'iconTone' => 'bg-orange-500/15 text-orange-300',
                ],
                [
                    'icon' => 'utensils-crossed',
                    'label' => __('student.dashboard.kpis.total_meals'),
                    'value' => localize_number($kpis['meals']['value']),
                    'previous' => localize_number($kpis['meals']['previous']),
                    'change' => $kpis['meals']['change'],
                    'tone' => 'text-sky-300',
                    'iconTone' => 'bg-sky-500/15 text-sky-300',
                ],
                [
                    'icon' => 'calculator',
                    'label' => __('student.dashboard.kpis.meal_rate'),
                    'value' => '৳'.localize_money($kpis['meal_rate']['value']),
                    'previous' => '৳'.localize_money($kpis['meal_rate']['previous']),
                    'change' => $kpis['meal_rate']['change'],
                    'tone' => 'text-teal-300',
                    'iconTone' => 'bg-teal-500/15 text-teal-300',
                ],
                [
                    'icon' => 'users',
                    'label' => __('student.dashboard.kpis.members'),
                    'value' => localize_number($kpis['members']['active']),
                    'previous' => null,
                    'change' => null,
                    'tone' => 'text-lime-300',
                    'iconTone' => 'bg-lime-500/15 text-lime-300',
                    'sub' => localize_number($kpis['members']['active']).' '.__('student.dashboard.active').' · '.localize_number($kpis['members']['inactive']).' '.__('student.dashboard.inactive'),
                ],
                [
                    'icon' => 'alert-circle',
                    'label' => __('student.dashboard.kpis.dues'),
                    'value' => '৳'.localize_money($kpis['dues']['value']),
                    'previous' => '৳'.localize_money($kpis['dues']['previous']),
                    'change' => $kpis['dues']['change'],
                    'tone' => 'text-red-300',
                    'iconTone' => 'bg-red-500/15 text-red-300',
                    'sub' => localize_number($kpis['dues']['count']).' '.__('student.dashboard.members_due'),
                ],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="glass card-hover rounded-xl p-3 sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="kpi-label font-bn">{{ $card['label'] }}</p>
                        <p class="mt-1 truncate text-base font-bold sm:text-xl {{ $card['tone'] }}">{{ $card['value'] }}</p>
                    </div>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $card['iconTone'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>

                @if(! empty($card['sub']))
                    <p class="mt-1.5 truncate text-[10px] text-gray-500 font-bn">{{ $card['sub'] }}</p>
                @else
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px] font-bn">
                        <span class="{{ $changePill($card['change']) }} inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-bold">
                            @if($card['change'] === null)
                                —
                            @else
                                {{ $card['change'] >= 0 ? '▲' : '▼' }} {{ localize_number(abs($card['change'])) }}%
                            @endif
                        </span>
                        <span class="truncate text-gray-500">{{ __('student.dashboard.previous') }}: {{ $card['previous'] }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </section>

    {{-- Analytics --}}
    <section class="grid gap-4 xl:grid-cols-5">
        <div class="glass-strong rounded-2xl p-4 sm:p-5 xl:col-span-3">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold font-bn text-white">{{ __('student.dashboard.income_expense') }}</h3>
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.income_expense_hint') }}</p>
                </div>
                <span class="rounded-full bg-white/5 px-2.5 py-1 text-[10px] text-gray-400 font-bn">{{ __('student.dashboard.last_six_months') }}</span>
            </div>
            <div class="h-64 sm:h-72"><canvas id="incomeExpenseChart"></canvas></div>
        </div>

        <div class="glass-strong rounded-2xl p-4 sm:p-5 xl:col-span-2">
            <div class="mb-3">
                <h3 class="text-sm font-bold font-bn text-white">{{ __('student.dashboard.expense_breakdown') }}</h3>
                <p class="text-[10px] text-gray-500 font-bn">{{ $monthLabel }}</p>
            </div>
            <div class="relative mx-auto h-48 w-48 sm:h-52 sm:w-52">
                <canvas id="expenseBreakdownChart"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.total_expense') }}</span>
                    <span class="text-lg font-bold text-white">৳{{ localize_money($charts['breakdown']['total']) }}</span>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-x-3 gap-y-2">
                @foreach($charts['breakdown']['labels'] as $index => $label)
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ ['#34D399', '#F59E0B', '#38BDF8', '#A78BFA'][$index] ?? '#94A3B8' }}"></span>
                        <span class="min-w-0 flex-1 truncate text-[10px] text-gray-400 font-bn">{{ $label }}</span>
                        <span class="text-[10px] font-bold text-gray-200">{{ localize_number($charts['breakdown']['percentages'][$index] ?? 0) }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Member overview + activities --}}
    <section class="grid gap-4 xl:grid-cols-5">
        <div class="glass rounded-2xl p-4 sm:p-5 xl:col-span-3">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold font-bn text-white">{{ __('student.dashboard.member_overview') }}</h3>
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.member_overview_hint') }}</p>
                </div>
                <form method="GET" action="{{ route('student.dashboard') }}" class="flex items-center gap-1.5">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500"></i>
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('student.dashboard.search_members') }}"
                               class="field w-40 py-1.5 pl-8 text-xs sm:w-48">
                    </div>
                    <button type="submit" class="btn-ghost rounded-lg px-2.5 py-1.5 text-[11px] text-gray-200 font-bn">{{ __('student.common.search') }}</button>
                </form>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('student.dashboard.member') }}</th>
                            <th>{{ __('student.dashboard.table_meals') }}</th>
                            <th>{{ __('student.dashboard.table_deposit') }}</th>
                            <th>{{ __('student.dashboard.table_meal_cost') }}</th>
                            <th>{{ __('student.dashboard.table_balance') }}</th>
                            <th>{{ __('student.common.status') }}</th>
                            <th>{{ __('student.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overview as $row)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-[10px] font-bold text-emerald-300">{{ mb_strtoupper(mb_substr($row['member']?->name ?? '?', 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-bold text-white font-bn">{{ $row['member']?->name ?? '—' }}</p>
                                            <p class="truncate text-[10px] text-gray-500">{{ $row['member']?->email ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-bold text-sky-300">{{ localize_number($row['meals']) }}</td>
                                <td>৳{{ localize_money($row['deposits']) }}</td>
                                <td>৳{{ localize_money($row['meal_cost']) }}</td>
                                <td class="font-bold {{ $row['balance'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">{{ $row['balance'] >= 0 ? '+' : '-' }}৳{{ localize_money(abs($row['balance'])) }}</td>
                                <td>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bn {{ $row['status'] === 'due' ? 'bg-red-500/15 text-red-300' : ($row['status'] === 'receivable' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-gray-300') }}">
                                        {{ __('student.settlements.balance_statuses.'.$row['status']) }}
                                    </span>
                                </td>
                                <td>
                                    @if($row['member'])
                                        <a href="{{ route('student.mess.reports.show', [$mess, 'member-statement', 'month' => $month, 'member' => $row['member']->getKey()]) }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-white/5 px-2 py-1 text-[10px] text-emerald-300 transition hover:bg-emerald-500/15 font-bn">
                                            <i data-lucide="arrow-up-right" class="h-3 w-3"></i>
                                            {{ __('student.dashboard.view_details') }}
                                        </a>
                                    @else
                                        <span class="text-[10px] text-gray-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-xs text-gray-500 font-bn">{{ __('student.dashboard.no_members_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $overview->links() }}</div>
        </div>

        <div class="glass rounded-2xl p-4 sm:p-5 xl:col-span-2">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-bold font-bn text-white">{{ __('student.dashboard.recent_activity') }}</h3>
                <span class="rounded-full bg-white/5 px-2 py-0.5 text-[10px] text-gray-500 font-bn">{{ $monthLabel }}</span>
            </div>

            <div class="student-scroll max-h-[430px] space-y-0 overflow-y-auto pr-1">
                @forelse($summary['activities'] as $activity)
                    <a href="{{ $activity['url'] }}" class="group flex gap-3 rounded-lg px-1 py-2.5 transition hover:bg-white/5">
                        <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300">
                            <i data-lucide="{{ $activityIcons[$activity['kind']] ?? 'activity' }}" class="h-3.5 w-3.5"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <span class="text-[11px] font-bold text-white font-bn">{{ $activity['title'] }}</span>
                                @if($activity['amount'] !== null)
                                    <span class="text-[10px] font-bold text-emerald-300">৳{{ localize_money($activity['amount']) }}</span>
                                @endif
                            </span>
                            <span class="mt-0.5 block truncate text-[10px] text-gray-400 font-bn">{{ $activity['description'] }}</span>
                            <span class="mt-0.5 block truncate text-[9px] text-gray-600 font-bn">
                                @if($activity['actor'])
                                    {{ __('student.dashboard.by_actor', ['name' => $activity['actor']]) }} ·
                                @endif
                                {{ $activity['time']?->diffForHumans() }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <i data-lucide="history" class="mb-2 h-6 w-6 text-gray-600"></i>
                        <p class="text-xs text-gray-500 font-bn">{{ __('student.dashboard.no_activity') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Quick actions + settlement --}}
    <section class="grid gap-4 xl:grid-cols-3">
        <div class="glass rounded-2xl p-4 sm:p-5 xl:col-span-2">
            <h3 class="mb-3 text-sm font-bold font-bn text-white">{{ __('student.dashboard.quick_actions') }}</h3>
            <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                <a href="{{ route('student.mess.meals.index', [$mess, 'month' => $month]) }}#meal-form"
                   class="card-hover group flex items-center gap-3 rounded-xl border border-emerald-400/20 bg-emerald-500/10 p-3 sm:p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300"><i data-lucide="utensils" class="h-5 w-5"></i></span>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-bold text-white font-bn">{{ __('student.dashboard.actions.add_meal') }}</span>
                        <span class="block truncate text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.actions.add_meal_hint') }}</span>
                    </span>
                </a>

                @if($canManageBazar)
                    <a href="{{ route('student.mess.bazar.index', [$mess, 'month' => $month]) }}#bazar-form"
                       class="card-hover group flex items-center gap-3 rounded-xl border border-orange-400/20 bg-orange-500/10 p-3 sm:p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-300"><i data-lucide="shopping-basket" class="h-5 w-5"></i></span>
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-bold text-white font-bn">{{ __('student.dashboard.actions.add_bazar') }}</span>
                            <span class="block truncate text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.actions.add_bazar_hint') }}</span>
                        </span>
                    </a>
                @endif

                <a href="{{ route('student.mess.deposits.index', [$mess, 'month' => $month]) }}#deposit-form"
                   class="card-hover group flex items-center gap-3 rounded-xl border border-sky-400/20 bg-sky-500/10 p-3 sm:p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/20 text-sky-300"><i data-lucide="hand-coins" class="h-5 w-5"></i></span>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-bold text-white font-bn">{{ __('student.dashboard.actions.add_deposit') }}</span>
                        <span class="block truncate text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.actions.add_deposit_hint') }}</span>
                    </span>
                </a>

                @if($canViewReports)
                    <a href="{{ route('student.mess.reports.index', [$mess, 'month' => $month]) }}"
                       class="card-hover group flex items-center gap-3 rounded-xl border border-violet-400/20 bg-violet-500/10 p-3 sm:p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-500/20 text-violet-300"><i data-lucide="file-bar-chart" class="h-5 w-5"></i></span>
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-bold text-white font-bn">{{ __('student.dashboard.actions.view_reports') }}</span>
                            <span class="block truncate text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.actions.view_reports_hint') }}</span>
                        </span>
                    </a>
                @endif
            </div>
        </div>

        <div class="glass rounded-2xl p-4 sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold font-bn text-white">{{ __('student.dashboard.next_settlement') }}</h3>
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.settlement_period') }}: {{ $monthLabel }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold font-bn {{ $settlement['status'] === 'finalized' ? 'bg-emerald-500/15 text-emerald-300' : ($settlement['status'] === 'draft' ? 'bg-sky-500/15 text-sky-300' : 'bg-orange-500/15 text-orange-300') }}">
                    {{ __('student.dashboard.settlement_statuses.'.$settlement['status']) }}
                </span>
            </div>

            <div class="mt-4 space-y-2 text-[11px] font-bn">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">{{ __('student.dashboard.settlement_due') }}</span>
                    <span class="font-bold text-white">{{ $settlement['period_end']->format('d M Y') }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">{{ __('student.settlements.meal_rate') }}</span>
                    <span class="font-bold text-teal-300">৳{{ localize_money($settlement['totals']['meal_rate']) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">{{ __('student.settlements.total_outstanding_dues') }}</span>
                    <span class="font-bold text-red-300">৳{{ localize_money($settlement['totals']['total_outstanding_dues']) }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-gray-400">{{ __('student.settlements.total_receivable') }}</span>
                    <span class="font-bold text-emerald-300">৳{{ localize_money($settlement['totals']['total_receivable']) }}</span>
                </div>
            </div>

            @if($settlement['is_finalized'])
                <p class="mt-3 rounded-lg bg-emerald-500/10 px-3 py-2 text-[10px] text-emerald-300 font-bn">
                    {{ __('student.dashboard.finalized_on', ['date' => $settlement['record']?->finalized_at?->format('d M Y H:i') ?? '—']) }}
                </p>
            @else
                <p class="mt-3 rounded-lg bg-orange-500/10 px-3 py-2 text-[10px] text-orange-300 font-bn">
                    {{ __('student.dashboard.pending_settlement') }}
                </p>
            @endif

            <a href="{{ route('student.mess.settlements.index', [$mess, 'month' => $month]) }}"
               class="btn-emerald mt-3 flex w-full items-center justify-center gap-2 rounded-lg py-2 text-xs font-bold font-bn">
                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                {{ __('student.dashboard.view_settlement') }}
            </a>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (! window.Chart) {
        return;
    }

    const chartGrid = { color: 'rgba(148, 163, 184, 0.12)' };
    const chartTicks = { color: '#94a3b8' };
    const money = (value) => '৳' + Number(value || 0).toLocaleString();
    const incomeExpense = @js($charts['income_expense']);
    const breakdown = @js($charts['breakdown']);
    const breakdownColors = ['#34D399', '#F59E0B', '#38BDF8', '#A78BFA'];

    const incomeCanvas = document.getElementById('incomeExpenseChart');
    if (incomeCanvas) {
        new Chart(incomeCanvas, {
            type: 'bar',
            data: {
                labels: incomeExpense.labels,
                datasets: [
                    {
                        label: @js(__('student.dashboard.chart_deposits')),
                        data: incomeExpense.deposits,
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderRadius: 5,
                        maxBarThickness: 26,
                    },
                    {
                        label: @js(__('student.dashboard.chart_expenses')),
                        data: incomeExpense.expenses,
                        backgroundColor: 'rgba(249, 115, 22, 0.75)',
                        borderRadius: 5,
                        maxBarThickness: 26,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#94a3b8', boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                    tooltip: { callbacks: { label: (context) => context.dataset.label + ': ' + money(context.raw) } },
                },
                scales: {
                    x: { grid: chartGrid, ticks: chartTicks },
                    y: { grid: chartGrid, ticks: { ...chartTicks, callback: (value) => money(value) } },
                },
            },
        });
    }

    const breakdownCanvas = document.getElementById('expenseBreakdownChart');
    if (breakdownCanvas) {
        new Chart(breakdownCanvas, {
            type: 'doughnut',
            data: {
                labels: breakdown.labels,
                datasets: [{
                    data: breakdown.values,
                    backgroundColor: breakdownColors,
                    borderColor: '#050B14',
                    borderWidth: 3,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => context.label + ': ' + money(context.raw) + ' (' + (breakdown.percentages[context.dataIndex] ?? 0) + '%)',
                        },
                    },
                },
            },
        });
    }
});
</script>
@endsection
