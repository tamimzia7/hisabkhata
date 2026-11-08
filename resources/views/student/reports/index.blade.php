@extends('layouts.student')

@section('title', __('student.reports.title'))
@section('heading', __('student.reports.title'))
@section('subheading', __('student.reports.subtitle'))

@section('content')
@php
    $reports = [
        ['type' => 'daily-meal', 'icon' => '📆', 'label' => __('student.reports.types.daily_meal')],
        ['type' => 'monthly-meal', 'icon' => '🍽️', 'label' => __('student.reports.types.monthly_meal')],
        ['type' => 'member-meal', 'icon' => '👤', 'label' => __('student.reports.types.member_meal')],
        ['type' => 'bazar', 'icon' => '🛒', 'label' => __('student.reports.types.bazar')],
        ['type' => 'deposit', 'icon' => '💰', 'label' => __('student.reports.types.deposit')],
        ['type' => 'balance', 'icon' => '⚖️', 'label' => __('student.reports.types.balance')],
        ['type' => 'settlement', 'icon' => '📅', 'label' => __('student.reports.types.settlement')],
        ['type' => 'income-expense', 'icon' => '📈', 'label' => __('student.reports.types.income_expense')],
        ['type' => 'member-statement', 'icon' => '🧾', 'label' => __('student.reports.types.member_statement')],
        ['type' => 'outstanding-dues', 'icon' => '⚠️', 'label' => __('student.reports.types.outstanding_dues')],
        ['type' => 'receivable', 'icon' => '✅', 'label' => __('student.reports.types.receivable')],
    ];
@endphp
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.reports.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($reports as $report)
            <a href="{{ route('student.mess.reports.show', [$mess, $report['type'], 'month' => $month]) }}" class="glass group rounded-2xl p-4 transition hover:border-emerald-400/40 hover:bg-emerald-500/5">
                <span class="text-2xl">{{ $report['icon'] }}</span>
                <p class="mt-2 text-sm font-bold font-bn text-white group-hover:text-emerald-300">{{ $report['label'] }}</p>
                <p class="mt-1 text-[10px] text-gray-500 font-bn">{{ __('student.reports.open') }} →</p>
            </a>
        @endforeach
    </div>
</div>
@endsection
