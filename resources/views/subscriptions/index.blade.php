@extends('layouts.app')

@php
    $onboarding = $onboarding ?? false;
    $recommendedProduct = $recommendedProduct ?? null;

    $productIcons = [
        'utensils' => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
    ];

    $statusStyles = [
        'active' => 'border-emerald-500/25 bg-emerald-500/15 text-emerald-400',
        'trial' => 'border-sky-500/25 bg-sky-500/15 text-sky-400',
        'expired' => 'border-red-500/25 bg-red-500/15 text-red-400',
        'suspended' => 'border-amber-500/25 bg-amber-500/15 text-amber-400',
        'pending' => 'border-orange-500/25 bg-orange-500/15 text-orange-400',
        'cancelled' => 'border-white/10 bg-white/10 text-gray-400',
        'inactive' => 'border-white/10 bg-white/5 text-gray-400',
    ];
@endphp

@section('title', $onboarding ? __('subscription.onboarding.title') : __('subscription.page.title'))

@section('content')
<div class="min-h-screen flex relative z-10" x-data="{ billing: 'monthly' }">
    @include('partials.client-sidebar')

    <main class="flex-1 overflow-x-hidden p-3 md:p-6">
        <div class="mx-auto max-w-6xl space-y-6">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    @if($onboarding)
                        <p class="text-[10px] uppercase tracking-[0.2em] text-emerald-400">{{ __('subscription.onboarding.eyebrow') }}</p>
                        <h1 class="mt-1 text-2xl font-bold font-display text-white sm:text-3xl">{{ __('subscription.onboarding.title') }}</h1>
                        <p class="mt-1 max-w-2xl text-sm text-gray-400 font-bn">{{ __('subscription.onboarding.description') }}</p>
                    @else
                        <p class="text-[10px] uppercase tracking-[0.2em] text-emerald-400">{{ __('site.common.brand') }}</p>
                        <h1 class="mt-1 text-2xl font-bold font-display text-white sm:text-3xl">{{ __('subscription.page.title') }}</h1>
                        <p class="mt-1 max-w-2xl text-sm text-gray-400 font-bn">{{ __('subscription.page.description') }}</p>
                    @endif
                </div>

                <x-notifications.bell />
            </header>

            @if($onboarding)
                <section class="glass-strong rounded-2xl p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-white font-bn">{{ __('subscription.onboarding.notice') }}</p>
                            <p class="mt-1 text-xs text-gray-400 font-bn">{{ __('subscription.onboarding.notice_hint') }}</p>
                        </div>
                    </div>
                </section>
            @else
                <section class="grid gap-3 sm:grid-cols-3">
                <div class="glass flex items-center gap-3 rounded-2xl p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 11 3 3L22 4"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-2xl font-bold font-display text-white">{{ localize_number($overview['active']) }}</p>
                        <p class="truncate text-[11px] text-gray-400 font-bn">{{ __('subscription.overview.active') }}</p>
                        <p class="truncate text-[10px] text-gray-500 font-bn">{{ __('subscription.overview.across_products') }}</p>
                    </div>
                </div>

                <div class="glass flex items-center gap-3 rounded-2xl p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.9 5.8L20 10l-6.1 1.2L12 17l-1.9-5.8L4 10l6.1-1.2L12 3Z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-2xl font-bold font-display text-white">{{ localize_number($overview['trialEligible']) }}</p>
                        <p class="truncate text-[11px] text-gray-400 font-bn">{{ __('subscription.overview.trial_eligible') }}</p>
                        <p class="truncate text-[10px] text-gray-500 font-bn">{{ __('subscription.overview.available_now') }}</p>
                    </div>
                </div>

                <div class="glass flex items-center gap-3 rounded-2xl p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-2xl font-bold font-display text-white">{{ localize_number($overview['upcoming']) }}</p>
                        <p class="truncate text-[11px] text-gray-400 font-bn">{{ __('subscription.overview.upcoming') }}</p>
                        <p class="truncate text-[10px] text-gray-500 font-bn">{{ __('subscription.overview.window', ['days' => localize_number($overview['windowDays'])]) }}</p>
                    </div>
                </div>
                </section>
            @endif

            <section>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold font-display text-white sm:text-xl">{{ __('subscription.choose_product') }}</h2>
                    </div>

                    <div class="inline-flex w-full rounded-xl border border-white/10 bg-white/5 p-1 sm:w-auto" role="group" aria-label="{{ __('subscription.billing.label') }}">
                        <button type="button" @click="billing = 'monthly'"
                                :class="billing === 'monthly' ? 'bg-emerald-500 text-black shadow' : 'text-gray-300 hover:text-white'"
                                class="flex-1 rounded-lg px-4 py-1.5 text-xs font-bold transition font-bn sm:flex-none">{{ __('subscription.billing.monthly') }}</button>
                        <button type="button" @click="billing = 'yearly'"
                                :class="billing === 'yearly' ? 'bg-emerald-500 text-black shadow' : 'text-gray-300 hover:text-white'"
                                class="flex-1 rounded-lg px-4 py-1.5 text-xs font-bold transition font-bn sm:flex-none">{{ __('subscription.billing.yearly') }}</button>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($products as $item)
                        <article class="glass card-hover flex h-full flex-col rounded-2xl p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">{!! $productIcons[$item['icon']] ?? $productIcons['utensils'] !!}</svg>
                                </span>
                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-bold font-bn {{ $statusStyles[$item['status']] ?? $statusStyles['inactive'] }}">
                                    {{ __('subscription.status.'.$item['status']) }}
                                </span>
                            </div>

                            <h3 class="mt-4 text-base font-bold font-bn text-white">{{ $item['productName'] }}</h3>
                            <p class="mt-0.5 text-[11px] uppercase tracking-wide text-emerald-400 font-bn">{{ $item['audience'] }}</p>
                            @if($onboarding && $recommendedProduct === $item['product'])
                                <span class="mt-2 inline-flex w-fit rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold font-bn text-emerald-400">
                                    {{ __('subscription.onboarding.recommended') }}
                                </span>
                            @endif
                            <p class="mt-2 text-xs leading-relaxed text-gray-400 font-bn">{{ $item['description'] }}</p>

                            <ul class="mt-4 space-y-1.5">
                                @foreach($item['features'] as $feature)
                                    <li class="flex items-start gap-2 text-xs text-gray-300 font-bn">
                                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-auto space-y-3 pt-5">
                                @if($item['currentSubscription'])
                                    <div class="space-y-1 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[11px] text-gray-400 font-bn">
                                        <div class="flex items-center justify-between gap-2">
                                            <span>{{ __('subscription.card.plan') }}</span>
                                            <span class="truncate text-right text-gray-200">{{ $item['currentSubscription']->plan?->name ?? '—' }}</span>
                                        </div>
                                        @if($item['currentSubscription']->ends_at)
                                            <div class="flex items-center justify-between gap-2">
                                                <span>{{ __('subscription.card.expires') }}</span>
                                                <span class="text-gray-200">{{ $item['currentSubscription']->ends_at->format('d M Y') }}</span>
                                            </div>
                                        @endif
                                        @if(in_array($item['status'], ['active', 'trial'], true) && $item['remainingDays'] > 0)
                                            <div class="flex items-center justify-between gap-2">
                                                <span>{{ __('subscription.card.remaining') }}</span>
                                                <span class="text-gray-200">{{ localize_number($item['remainingDays']) }} {{ __('subscription.plans.days') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if($item['status'] === 'trial')
                                    <p class="rounded-lg bg-sky-500/10 px-3 py-2 text-[11px] text-sky-300 font-bn">{{ __('subscription.card.trial_active', ['days' => localize_number($item['trialRemainingDays'])]) }}</p>
                                @elseif($item['trialEligible'])
                                    <p class="rounded-lg bg-emerald-500/10 px-3 py-2 text-[11px] text-emerald-300 font-bn">{{ __('subscription.card.trial_available', ['days' => localize_number($item['trialDays'])]) }}</p>
                                @elseif($item['trial'])
                                    <p class="rounded-lg bg-white/5 px-3 py-2 text-[11px] text-gray-400 font-bn">{{ __('subscription.trial.used') }}</p>
                                @endif

                                @if($item['status'] === 'expired')
                                    <p class="rounded-lg bg-red-500/10 px-3 py-2 text-[11px] text-red-300 font-bn">{{ __('subscription.card.expired_hint') }}</p>
                                @elseif($item['status'] === 'cancelled')
                                    <p class="rounded-lg bg-white/5 px-3 py-2 text-[11px] text-gray-400 font-bn">{{ __('subscription.card.cancelled_hint') }}</p>
                                @endif

                                <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-500 font-bn">{{ __('subscription.billing.starting_at') }}</p>

                                    <div x-show="billing === 'monthly'">
                                        @if($item['monthlyPlan'])
                                            <p class="mt-0.5 flex items-baseline gap-1">
                                                <span class="text-2xl font-bold font-display text-white">৳{{ localize_money($item['monthlyPrice']) }}</span>
                                                <span class="text-[11px] text-gray-400 font-bn">{{ __('subscription.billing.per_month') }}</span>
                                            </p>
                                            <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.billing.billed_monthly') }}</p>
                                        @else
                                            <p class="mt-1 text-[11px] text-gray-500 font-bn">{{ __('subscription.plans.no_plans') }}</p>
                                        @endif
                                    </div>

                                    <div x-show="billing === 'yearly'" x-cloak>
                                        @if($item['yearlyPlan'])
                                            <p class="mt-0.5 flex items-baseline gap-1">
                                                <span class="text-2xl font-bold font-display text-white">৳{{ localize_money($item['yearlyPrice']) }}</span>
                                                <span class="text-[11px] text-gray-400 font-bn">{{ __('subscription.billing.per_year') }}</span>
                                            </p>
                                            <p class="text-[10px] text-gray-500 font-bn">
                                                {{ __('subscription.billing.billed_yearly') }}
                                                @if($item['annualSavings'] > 0)
                                                    · <span class="text-emerald-400">{{ __('subscription.billing.save', ['amount' => '৳'.localize_money($item['annualSavings'])]) }}</span>
                                                @endif
                                            </p>
                                        @else
                                            <p class="mt-1 text-[11px] text-gray-500 font-bn">{{ __('subscription.plans.no_plans') }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if($item['cta']['method'] === 'post')
                                    <form method="POST" action="{{ $item['cta']['url'] }}" onsubmit="this.querySelector('button').disabled = true;">
                                        @csrf
                                        <button type="submit" class="btn-green flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-bold font-bn">{{ $item['cta']['label'] }}</button>
                                    </form>

                                    @if($item['secondaryCta'])
                                        <a href="{{ $item['secondaryCta']['url'] }}"
                                           :href="@js($item['secondaryCta']['url']) + '?billing=' + billing"
                                           class="flex w-full items-center justify-center rounded-xl border border-white/10 px-4 py-2.5 text-sm font-bold text-gray-300 transition hover:bg-white/5 font-bn">{{ $item['secondaryCta']['label'] }}</a>
                                    @endif
                                @else
                                    <a href="{{ $item['cta']['url'] }}"
                                       :href="@js($item['cta']['url']) + '?billing=' + billing"
                                       class="btn-green flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-bold font-bn">{{ $item['cta']['label'] }}</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
</div>
@endsection
