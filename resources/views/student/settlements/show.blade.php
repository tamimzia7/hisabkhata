@extends('layouts.student')

@section('title', __('student.settlements.statement'))
@section('heading', __('student.settlements.statement'))
@section('subheading', $mess->name.' · '.$settlement->month.' v'.$settlement->version)

@section('content')
<div class="space-y-4">
    <div class="glass flex flex-wrap items-center justify-between gap-2 rounded-xl p-3">
        <span class="rounded-full px-3 py-1 text-[11px] font-bold font-bn {{ $settlement->status === 'finalized' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-orange-500/15 text-orange-400' }}">
            {{ __('student.settlements.statuses.'.$settlement->status) }} · v{{ $settlement->version }}
        </span>
        <div class="flex flex-wrap gap-2">
            <button onclick="window.print()" class="btn-ghost rounded-lg px-3 py-2 text-[11px] text-gray-200 font-bn">🖨 {{ __('student.settlements.print') }}</button>
            <a href="{{ route('student.mess.settlements.index', [$mess, 'month' => $settlement->month]) }}" class="btn-ghost rounded-lg px-3 py-2 text-[11px] text-gray-200 font-bn">{{ __('student.common.back') }}</a>
        </div>
    </div>

    <div class="glass-strong rounded-2xl p-5" id="settlement-print">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-2 border-b border-white/10 pb-3">
            <div>
                <h2 class="text-lg font-bold font-display font-bn text-white">{{ $mess->name }}</h2>
                <p class="text-[11px] text-gray-400 font-bn">{{ __('student.settlements.statement_for') }} {{ $settlement->month }}</p>
            </div>
            <div class="text-right text-[11px] text-gray-400 font-bn">
                <p>{{ __('student.settlements.finalized_at') }}: {{ $settlement->finalized_at?->format('d M Y H:i') ?? '—' }}</p>
                <p>{{ __('student.settlements.finalized_by') }}: {{ $settlement->finalizer?->name ?? '—' }}</p>
            </div>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach([
                ['label' => __('student.settlements.total_deposits'), 'value' => '৳'.localize_money($settlement->total_deposits)],
                ['label' => __('student.settlements.total_bazar'), 'value' => '৳'.localize_money($settlement->total_bazar)],
                ['label' => __('student.settlements.total_meal_expenses'), 'value' => '৳'.localize_money($settlement->total_meal_expenses)],
                ['label' => __('student.settlements.total_meals'), 'value' => localize_number($settlement->total_meals)],
                ['label' => __('student.settlements.meal_rate'), 'value' => '৳'.localize_money($settlement->meal_rate)],
                ['label' => __('student.settlements.total_meal_cost'), 'value' => '৳'.localize_money($settlement->total_meal_cost)],
                ['label' => __('student.settlements.total_personal_expenses'), 'value' => '৳'.localize_money($settlement->total_personal_expenses)],
                ['label' => __('student.settlements.total_outstanding_dues'), 'value' => '৳'.localize_money($settlement->total_outstanding_dues)],
            ] as $card)
                <div class="rounded-lg bg-white/5 p-3"><p class="kpi-label font-bn">{{ $card['label'] }}</p><p class="text-base font-bold text-emerald-400">{{ $card['value'] }}</p></div>
            @endforeach
        </div>

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
                    @foreach($settlement->details as $detail)
                        @php($status = $detail->balance > 0 ? 'receivable' : ($detail->balance < 0 ? 'due' : 'settled'))
                        <tr>
                            <td class="font-bn font-bold text-white">{{ $detail->member?->name ?? '—' }}</td>
                            <td>৳{{ localize_money($detail->opening_balance) }}</td>
                            <td>{{ localize_number($detail->meals) }}</td>
                            <td>৳{{ localize_money($detail->meal_cost) }}</td>
                            <td>৳{{ localize_money($detail->deposits) }}</td>
                            <td>৳{{ localize_money($detail->refunds) }}</td>
                            <td>৳{{ localize_money($detail->adjustments) }}</td>
                            <td>৳{{ localize_money($detail->other_allocated) }}</td>
                            <td>৳{{ localize_money($detail->personal_expenses) }}</td>
                            <td class="font-bold {{ $detail->balance >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $detail->balance >= 0 ? '+' : '-' }}৳{{ localize_money(abs($detail->balance)) }}</td>
                            <td><span class="rounded-full px-2 py-0.5 text-[10px] font-bn {{ $status === 'due' ? 'bg-red-500/15 text-red-300' : ($status === 'receivable' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-gray-300') }}">{{ __('student.settlements.balance_statuses.'.$status) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap justify-between gap-2 border-t border-white/10 pt-3 text-xs font-bn">
            <span class="text-gray-400">{{ __('student.settlements.total_shared_expenses') }}: <b class="text-gray-200">৳{{ localize_money($settlement->total_other_expenses) }}</b></span>
            <span class="text-gray-400">{{ __('student.settlements.total_receivable') }}: <b class="text-emerald-400">৳{{ localize_money($settlement->total_receivable) }}</b></span>
            <span class="text-gray-400">{{ __('student.settlements.total_surplus') }}: <b class="{{ $settlement->total_surplus >= 0 ? 'text-emerald-400' : 'text-red-400' }}">৳{{ localize_money($settlement->total_surplus) }}</b></span>
        </div>
    </div>

    @if($settlement->isFinalized() && $canManage)
        <form method="POST" action="{{ route('student.mess.settlements.reopen', [$mess, $settlement]) }}" class="glass rounded-2xl p-4"
              onsubmit="return confirm(@js(__('student.settlements.confirm_reopen')))">
            @csrf
            <h3 class="text-sm font-bold text-orange-300 font-bn">{{ __('student.settlements.reopen') }}</h3>
            <p class="mt-1 text-[11px] text-gray-500 font-bn">{{ __('student.settlements.reopen_note') }}</p>
            <input name="reason" required minlength="5" maxlength="500" placeholder="{{ __('student.settlements.reopen_reason') }}" class="field mt-2 font-bn">
            <button type="submit" class="mt-2 rounded-lg bg-orange-500/15 px-4 py-2 text-xs font-bold text-orange-300 font-bn">{{ __('student.settlements.reopen') }}</button>
        </form>
    @endif
</div>

<style>@media print { body * { visibility: hidden; } #settlement-print, #settlement-print * { visibility: visible; } #settlement-print { position: absolute; inset: 0; } }</style>
@endsection
