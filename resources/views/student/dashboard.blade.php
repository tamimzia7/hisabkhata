@extends('layouts.student')

@section('title', __('student.dashboard.title'))
@section('heading', __('student.dashboard.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-5">
    <form method="GET" action="{{ route('student.dashboard') }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div class="min-w-[140px]">
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.dashboard.range_label') }}</label>
            <select name="range" onchange="this.form.submit()" class="field">
                @foreach (['today' => __('student.dashboard.ranges.today'), 'week' => __('student.dashboard.ranges.week'), 'month' => __('student.dashboard.ranges.month'), 'previous_month' => __('student.dashboard.ranges.previous_month'), 'custom' => __('student.dashboard.ranges.custom')] as $value => $label)
                    <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if($range === 'custom')
            <div>
                <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.from') }}</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="field">
            </div>
            <div>
                <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.to') }}</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="field">
            </div>
        @endif
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
        <span class="ml-auto rounded-full bg-white/5 px-3 py-1 text-[10px] text-gray-400 font-bn">
            {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
        </span>
    </form>

    <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['label' => __('student.dashboard.kpis.total_balance'), 'value' => '৳'.localize_money($summary['kpis']['total_balance']), 'tone' => 'text-emerald-400'],
            ['label' => __('student.dashboard.kpis.monthly_bazar'), 'value' => '৳'.localize_money($summary['kpis']['period_bazar']), 'tone' => 'text-orange-400'],
            ['label' => __('student.dashboard.kpis.total_meals'), 'value' => localize_number($summary['kpis']['period_meals']), 'tone' => 'text-sky-400'],
            ['label' => __('student.dashboard.kpis.meal_rate'), 'value' => '৳'.localize_money($summary['kpis']['meal_rate']), 'tone' => 'text-teal-400'],
            ['label' => __('student.dashboard.kpis.members'), 'value' => localize_number($summary['kpis']['total_members']), 'tone' => 'text-lime-400'],
            ['label' => __('student.dashboard.kpis.dues'), 'value' => '৳'.localize_money($summary['kpis']['outstanding_dues']), 'tone' => 'text-red-400'],
        ] as $card)
            <div class="glass rounded-xl p-3">
                <p class="kpi-label font-bn">{{ $card['label'] }}</p>
                <p class="mt-1 truncate text-base font-bold sm:text-xl {{ $card['tone'] }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass-strong rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.dashboard.income_expense') }}</h3>
            <div class="h-56"><canvas id="incomeExpenseChart"></canvas></div>
        </div>
        <div class="glass-strong rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.dashboard.daily_bazar') }}</h3>
            <div class="h-56"><canvas id="dailyBazarChart"></canvas></div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass rounded-2xl p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-bold font-bn">{{ __('student.dashboard.member_meals') }}</h3>
                <a href="{{ route('student.mess.meals.index', $mess) }}" class="text-[11px] text-emerald-400 font-bn">{{ __('student.common.view_all') }}</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('student.members.name') }}</th>
                            <th>{{ __('student.meals.breakfast') }}</th>
                            <th>{{ __('student.meals.lunch') }}</th>
                            <th>{{ __('student.meals.dinner') }}</th>
                            <th>{{ __('student.meals.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary['member_meals'] as $row)
                            <tr>
                                <td class="font-bn font-bold text-white">{{ $row['name'] }}</td>
                                <td>{{ localize_number($row['breakfast']) }}</td>
                                <td>{{ localize_number($row['lunch']) }}</td>
                                <td>{{ localize_number($row['dinner']) }}</td>
                                <td class="font-bold text-emerald-400">{{ localize_number($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-5 text-center text-gray-500 font-bn">{{ __('student.meals.no_entries') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-bold font-bn">{{ __('student.dashboard.member_balance') }}</h3>
                <span class="text-[11px] text-gray-500 font-bn">{{ __('student.dashboard.meal_rate') }}: ৳{{ localize_money($summary['preview']['totals']['meal_rate']) }}</span>
            </div>
            <div class="student-scroll max-h-64 space-y-1.5 overflow-y-auto pr-1">
                @forelse($summary['member_balances'] as $row)
                    <div class="flex items-center gap-2 rounded-lg bg-white/5 px-3 py-2">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-[10px] font-bold text-emerald-300">{{ mb_strtoupper(mb_substr($row['member']->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-white font-bn">{{ $row['member']->name }}</p>
                            <p class="text-[10px] text-gray-500">{{ localize_number($row['meals']) }} {{ __('student.reports.columns.meals') }}</p>
                        </div>
                        <span class="text-xs font-bold {{ $row['balance'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ $row['balance'] >= 0 ? '+' : '-' }}৳{{ localize_money(abs($row['balance'])) }}
                        </span>
                    </div>
                @empty
                    <p class="py-5 text-center text-xs text-gray-500 font-bn">{{ __('student.common.no_records') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.dashboard.recent_bazar') }}</h3>
            <div class="space-y-2">
                @forelse($summary['recent_bazar'] as $record)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-white/5 px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-white font-bn">{{ $record->category }}</p>
                            <p class="text-[10px] text-gray-500">{{ $record->purchase_date?->format('d M Y') }} · {{ $record->purchaser?->name ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-orange-400">৳{{ localize_money($record->total_amount) }}</span>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-gray-500 font-bn">{{ __('student.common.no_records') }}</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.dashboard.recent_deposits') }}</h3>
            <div class="space-y-2">
                @forelse($summary['recent_deposits'] as $deposit)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-white/5 px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-white font-bn">{{ $deposit->member?->name ?? '—' }}</p>
                            <p class="text-[10px] text-gray-500">{{ $deposit->deposit_date?->format('d M Y') }} · {{ __('student.deposits.types.'.$deposit->type) }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-emerald-400">৳{{ localize_money($deposit->amount) }}</span>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-gray-500 font-bn">{{ __('student.common.no_records') }}</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h3 class="text-sm font-bold font-bn">{{ __('student.dashboard.settlement') }}</h3>
                <a href="{{ route('student.mess.settlements.index', $mess) }}" class="text-[11px] text-emerald-400 font-bn">{{ __('student.common.view_all') }}</a>
            </div>
            @if($summary['settlement'])
                <div class="space-y-1.5 text-xs font-bn">
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.status') }}</span><span class="font-bold {{ $summary['settlement']->status === 'finalized' ? 'text-emerald-400' : 'text-orange-400' }}">{{ __('student.settlements.statuses.'.$summary['settlement']->status) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.meal_rate') }}</span><span class="font-bold">৳{{ localize_money($summary['settlement']->meal_rate) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.total_meals') }}</span><span class="font-bold">{{ localize_number($summary['settlement']->total_meals) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.total_surplus') }}</span><span class="font-bold {{ (float) $summary['settlement']->total_surplus >= 0 ? 'text-emerald-400' : 'text-red-400' }}">৳{{ localize_money($summary['settlement']->total_surplus) }}</span></div>
                </div>
            @else
                <p class="py-4 text-center text-xs text-gray-500 font-bn">{{ __('student.settlements.no_history') }}</p>
            @endif

            <h4 class="mt-4 mb-2 border-t border-white/10 pt-3 text-[11px] font-bold text-gray-300 font-bn">{{ __('student.dashboard.reminders') }}</h4>
            @forelse($summary['reminders'] as $reminder)
                <div class="mb-1 flex items-center justify-between text-[11px] font-bn">
                    <span class="truncate text-gray-300">{{ $reminder['member']->name }}</span>
                    <span class="text-red-400">৳{{ localize_money(abs($reminder['balance'])) }}</span>
                </div>
            @empty
                <p class="text-[11px] text-gray-500 font-bn">{{ __('student.dashboard.no_reminders') }}</p>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = { color: 'rgba(148, 163, 184, 0.12)' };
    const incomeExpense = @js($summary['income_expense']);
    const dailyBazar = @js($summary['daily_bazar']);

    const incomeCanvas = document.getElementById('incomeExpenseChart');
    if (incomeCanvas) {
        new Chart(incomeCanvas, {
            type: 'bar',
            data: {
                labels: incomeExpense.labels,
                datasets: [
                    { label: @js(__('student.reports.columns.income')), data: incomeExpense.income, backgroundColor: 'rgba(16, 185, 129, 0.7)', borderRadius: 4 },
                    { label: @js(__('student.reports.columns.expenses')), data: incomeExpense.expense, backgroundColor: 'rgba(249, 115, 22, 0.7)', borderRadius: 4 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#94a3b8' } } }, scales: { x: { grid, ticks: { color: '#94a3b8' } }, y: { grid, ticks: { color: '#94a3b8' } } } },
        });
    }

    const bazarCanvas = document.getElementById('dailyBazarChart');
    if (bazarCanvas) {
        new Chart(bazarCanvas, {
            type: 'line',
            data: {
                labels: dailyBazar.labels,
                datasets: [{ label: @js(__('student.dashboard.daily_bazar')), data: dailyBazar.values, borderColor: '#34D399', backgroundColor: 'rgba(52, 211, 153, 0.15)', fill: true, tension: 0.35 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#94a3b8' } } }, scales: { x: { grid, ticks: { color: '#94a3b8' } }, y: { grid, ticks: { color: '#94a3b8' } } } },
        });
    }
});
</script>
@endsection
