@extends('layouts.student')

@section('title', __('student.settlements.title'))
@section('heading', __('student.settlements.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.settlements.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.settlements.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            @if($locked)
                <span class="rounded-full bg-red-500/15 px-3 py-1 text-[10px] font-bold text-red-300 font-bn">🔒 {{ __('student.settlements.locked') }}</span>
            @elseif($canManage)
                <form method="POST" action="{{ route('student.mess.settlements.finalize', $mess) }}" onsubmit="return confirm(@js(__('student.settlements.confirm_finalize')))">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.settlements.finalize') }}</button>
                </form>
            @endif
            <a href="{{ route('student.mess.settlements.index', [$mess, 'month' => $month]) }}" class="btn-ghost rounded-lg px-3 py-2 text-[11px] text-gray-300 font-bn">↻</a>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
        @foreach([
            ['label' => __('student.settlements.total_deposits'), 'value' => '৳'.localize_money($preview['totals']['total_deposits']), 'tone' => 'text-emerald-400'],
            ['label' => __('student.settlements.total_bazar'), 'value' => '৳'.localize_money($preview['totals']['total_bazar']), 'tone' => 'text-orange-400'],
            ['label' => __('student.settlements.total_meal_expenses'), 'value' => '৳'.localize_money($preview['totals']['total_meal_expenses']), 'tone' => 'text-amber-400'],
            ['label' => __('student.settlements.total_meals'), 'value' => localize_number($preview['totals']['total_meals']), 'tone' => 'text-sky-400'],
            ['label' => __('student.settlements.meal_rate'), 'value' => '৳'.localize_money($preview['totals']['meal_rate']), 'tone' => 'text-teal-400'],
            ['label' => __('student.settlements.total_meal_cost'), 'value' => '৳'.localize_money($preview['totals']['total_meal_cost']), 'tone' => 'text-emerald-300'],
            ['label' => __('student.settlements.total_shared_expenses'), 'value' => '৳'.localize_money($preview['totals']['total_shared_expenses']), 'tone' => 'text-orange-300'],
            ['label' => __('student.settlements.total_personal_expenses'), 'value' => '৳'.localize_money($preview['totals']['total_personal_expenses']), 'tone' => 'text-sky-300'],
            ['label' => __('student.settlements.total_outstanding_dues'), 'value' => '৳'.localize_money($preview['totals']['total_outstanding_dues']), 'tone' => 'text-red-400'],
            ['label' => __('student.settlements.total_receivable'), 'value' => '৳'.localize_money($preview['totals']['total_receivable']), 'tone' => 'text-lime-400'],
        ] as $card)
            <div class="glass rounded-xl p-3"><p class="kpi-label font-bn">{{ $card['label'] }}</p><p class="text-base font-bold {{ $card['tone'] }}">{{ $card['value'] }}</p></div>
        @endforeach
    </div>

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.settlements.member_breakdown') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.members.name') }}</th>
                        <th>{{ __('student.settlements.opening_balance') }}</th>
                        <th>{{ __('student.settlements.meals') }}</th>
                        <th>{{ __('student.settlements.meal_cost') }}</th>
                        <th>{{ __('student.settlements.deposits') }}</th>
                        <th>{{ __('student.settlements.refunds') }}</th>
                        <th>{{ __('student.settlements.adjustments') }}</th>
                        <th>{{ __('student.settlements.other_allocated') }}</th>
                        <th>{{ __('student.settlements.personal_expenses') }}</th>
                        <th>{{ __('student.settlements.balance') }}</th>
                        <th>{{ __('student.common.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preview['members'] as $row)
                        <tr>
                            <td class="font-bn font-bold text-white">{{ $row['member']->name }}</td>
                            <td>৳{{ localize_money($row['opening_balance']) }}</td>
                            <td>{{ localize_number($row['meals']) }}</td>
                            <td>৳{{ localize_money($row['meal_cost']) }}</td>
                            <td>৳{{ localize_money($row['deposits']) }}</td>
                            <td>৳{{ localize_money($row['refunds']) }}</td>
                            <td>৳{{ localize_money($row['adjustments']) }}</td>
                            <td>৳{{ localize_money($row['shared_allocated']) }}</td>
                            <td>৳{{ localize_money($row['personal_expenses']) }}</td>
                            <td class="font-bold {{ $row['balance'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $row['balance'] >= 0 ? '+' : '-' }}৳{{ localize_money(abs($row['balance'])) }}</td>
                            <td><span class="rounded-full px-2 py-0.5 text-[10px] font-bn {{ $row['status'] === 'due' ? 'bg-red-500/15 text-red-300' : ($row['status'] === 'receivable' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-gray-300') }}">{{ __('student.settlements.balance_statuses.'.$row['status']) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-4 text-center text-gray-500 font-bn">{{ __('student.common.no_records') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-emerald-400/20 text-xs">
                        <td class="font-bn font-bold text-white">{{ __('student.common.total') }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_opening_balance']) }}</td>
                        <td class="font-bold">{{ localize_number($preview['totals']['total_meals']) }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_meal_cost']) }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_deposits']) }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_refunds']) }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_adjustments']) }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_shared_expenses']) }}</td>
                        <td class="font-bold">৳{{ localize_money($preview['totals']['total_personal_expenses']) }}</td>
                        <td class="font-bold {{ $preview['totals']['total_surplus'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">৳{{ localize_money($preview['totals']['total_surplus'] + $preview['totals']['total_opening_balance']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="mt-2 text-[10px] text-gray-500 font-bn">{{ __('student.settlements.formula_note') }}</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.settlements.income_expense') }}</h3>
            <div class="space-y-1.5 text-xs font-bn">
                <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.total_deposits') }}</span><span class="font-bold text-emerald-400">৳{{ localize_money($preview['totals']['total_deposits']) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.total_bazar') }}</span><span class="font-bold text-orange-400">৳{{ localize_money($preview['totals']['total_bazar']) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.total_meal_expenses') }}</span><span class="font-bold text-orange-400">৳{{ localize_money($preview['totals']['total_meal_expenses']) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">{{ __('student.settlements.total_other_expenses') }}</span><span class="font-bold text-orange-400">৳{{ localize_money($preview['totals']['total_other_expenses']) }}</span></div>
                <div class="flex justify-between border-t border-white/10 pt-1.5"><span class="text-gray-300">{{ __('student.settlements.total_surplus') }}</span><span class="font-bold {{ $preview['totals']['total_surplus'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">৳{{ localize_money($preview['totals']['total_surplus']) }}</span></div>
            </div>
        </div>

        <div class="glass rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.settlements.history') }}</h3>
            <div class="space-y-2">
                @forelse($history as $entry)
                    <div class="flex items-center justify-between gap-2 rounded-lg bg-white/5 px-3 py-2 text-xs">
                        <div class="min-w-0">
                            <p class="text-gray-200 font-bn">v{{ $entry->version }} · {{ __('student.settlements.statuses.'.$entry->status) }}</p>
                            <p class="text-[10px] text-gray-500">{{ $entry->finalized_at?->format('d M Y H:i') ?? $entry->created_at?->format('d M Y H:i') }}</p>
                        </div>
                        <a href="{{ route('student.mess.settlements.show', [$mess, $entry]) }}" class="shrink-0 text-[11px] text-emerald-400 font-bn">{{ __('student.common.view') }}</a>
                    </div>
                @empty
                    <p class="py-3 text-center text-xs text-gray-500 font-bn">{{ __('student.settlements.no_history') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
