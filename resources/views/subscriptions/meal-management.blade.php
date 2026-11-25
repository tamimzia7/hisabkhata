@extends('layouts.app')

@php
    $hasAccess = in_array($status, ['active', 'trial'], true);
    $featureIcons = ['users', 'utensils', 'shopping-cart', 'chart-pie', 'message-circle', 'shield-check'];
    $featureLabels = [
        ...$features,
        __('subscription.landing.whatsapp_notifications'),
        __('subscription.landing.secure'),
    ];
@endphp

@section('title', __('subscription.details.title', ['product' => $productName]))

@section('content')
<style>
    html body .meal-subscription,
    html body .meal-subscription * {
        font-weight: 400 !important;
    }

    html body .meal-subscription .font-display,
    html body .meal-subscription .font-bold,
    html body .meal-subscription .font-semibold {
        font-weight: 700 !important;
    }
</style>

<div class="meal-subscription relative z-10 flex min-h-screen" x-data="{}">
    @include('partials.client-sidebar')

    <main class="min-w-0 flex-1 overflow-x-hidden p-4 md:p-6 lg:p-8">
        <div class="mx-auto max-w-7xl space-y-8">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-400/25 bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-300 font-bn">
                            <i data-lucide="sparkles" class="h-3 w-3"></i>
                            {{ __('subscription.landing.eyebrow') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold font-bn {{ $hasAccess ? 'border-emerald-500/25 bg-emerald-500/15 text-emerald-400' : 'border-red-500/20 bg-red-500/10 text-red-400' }}">
                            <i data-lucide="{{ $hasAccess ? 'check-circle' : 'alert-circle' }}" class="h-3 w-3"></i>
                            {{ __('subscription.status.'.$status) }}
                        </span>
                    </div>
                    <h1 class="font-display text-2xl font-bold text-white sm:text-3xl">{{ __('subscription.details.title', ['product' => $productName]) }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-400 font-bn">{{ __('subscription.landing.description') }}</p>
                </div>

                <a href="{{ route('subscriptions.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-bold text-gray-200 transition hover:border-emerald-400/20 hover:bg-white/10 font-bn">
                    <i data-lucide="receipt" class="h-4 w-4 text-emerald-400"></i>
                    {{ __('subscription.view_all') }}
                </a>
            </header>

            @unless($hasAccess)
                <section class="glass relative overflow-hidden rounded-2xl border-l-4 border-red-500/70 p-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-red-500/10 blur-2xl"></div>

                    <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500/15 text-red-400">
                                <i data-lucide="shield-alert" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-base font-bold text-white font-bn">{{ __('subscription.landing.warning_title') }}</h2>
                                <p class="mt-1 text-sm leading-relaxed text-gray-400 font-bn">{{ __('subscription.landing.warning_hint') }}</p>
                            </div>
                        </div>

                        <a href="#plans" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-2 text-sm font-bold text-red-300 transition hover:bg-red-500/20 font-bn">
                            {{ __('subscription.landing.view_plans') }}
                        </a>
                    </div>
                </section>
            @endunless

            <section class="glass-strong glow-green relative overflow-hidden rounded-3xl p-1">
                <div class="relative overflow-hidden rounded-2xl bg-emerald-950/30 p-6 lg:p-8">
                    <div class="pointer-events-none absolute -left-10 -top-10 h-48 w-48 rounded-full bg-amber-400/10 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-20 right-0 h-56 w-56 rounded-full bg-emerald-500/10 blur-3xl"></div>

                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-amber-400/25 bg-amber-400/10 text-amber-300 shadow-lg shadow-amber-500/10">
                                <i data-lucide="gift" class="h-7 w-7"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-bold text-white sm:text-2xl font-bn">{{ __('subscription.trial.title') }}</h2>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/25 bg-amber-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-amber-300 font-bn">
                                        <i data-lucide="star" class="h-3 w-3"></i>
                                        {{ __('subscription.landing.trial_badge') }}
                                    </span>
                                </div>
                                <p class="mt-2 max-w-xl text-sm leading-relaxed text-gray-400 font-bn">{{ __('subscription.landing.trial_description') }}</p>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-400/20 bg-emerald-400/10 text-emerald-400" title="{{ __('subscription.landing.trial_no_card') }}" aria-label="{{ __('subscription.landing.trial_no_card') }}">
                                        <i data-lucide="credit-card-off" class="h-4 w-4"></i>
                                    </span>
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-400/20 bg-emerald-400/10 text-emerald-400" title="{{ __('subscription.landing.trial_cancel') }}" aria-label="{{ __('subscription.landing.trial_cancel') }}">
                                        <i data-lucide="calendar-x" class="h-4 w-4"></i>
                                    </span>
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-400/20 bg-emerald-400/10 text-emerald-400" title="{{ __('subscription.landing.trial_unlocked') }}" aria-label="{{ __('subscription.landing.trial_unlocked') }}">
                                        <i data-lucide="unlock" class="h-4 w-4"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full shrink-0 lg:w-auto">
                            @if($trialEligible)
                                <form method="POST" action="{{ route('subscriptions.trial', $product) }}" x-data="{ submitting: false }" @submit="submitting = true">
                                    @csrf
                                    <button type="submit" :disabled="submitting" class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 px-8 py-3.5 text-sm font-bold text-amber-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60 font-bn">
                                        <span x-show="!submitting" class="inline-flex items-center gap-2">
                                            <i data-lucide="rocket" class="h-4 w-4"></i>
                                            {{ __('subscription.landing.trial_start') }}
                                        </span>
                                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                            {{ __('subscription.trial.activating') }}
                                        </span>
                                    </button>
                                </form>
                            @elseif($hasAccess)
                                <a href="{{ route('student.dashboard') }}" class="btn-emerald flex w-full items-center justify-center gap-2 rounded-xl px-8 py-3.5 text-sm font-bold font-bn">
                                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                    {{ __('subscription.open') }}
                                </a>
                            @else
                                <a href="#plans" class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-3.5 text-sm font-bold text-gray-200 transition hover:bg-white/10 font-bn">
                                    <i data-lucide="credit-card" class="h-4 w-4 text-emerald-400"></i>
                                    {{ __('subscription.cta.view_plans') }}
                                </a>
                            @endif
                            <p class="mt-2 text-center text-[10px] text-gray-500 font-bn">{{ __('subscription.landing.trial_no_auto_bill') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="glass flex flex-col rounded-3xl p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-400">
                            <i data-lucide="rocket" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-white font-bn">{{ __('subscription.landing.features_title') }}</h2>
                            <p class="mt-1 text-xs leading-relaxed text-gray-500 font-bn">{{ __('subscription.landing.features_hint') }}</p>
                        </div>
                    </div>

                    <div class="mt-5 grid flex-1 gap-3 sm:grid-cols-2">
                        @foreach($featureLabels as $index => $feature)
                            <div class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-emerald-400/25">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 transition group-hover:bg-emerald-500/15">
                                    <i data-lucide="{{ $featureIcons[$index] ?? 'check' }}" class="h-4 w-4"></i>
                                </span>
                                <span class="text-sm text-gray-300 font-bn">{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section id="plans" class="glass flex flex-col rounded-3xl p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-400">
                                <i data-lucide="wallet" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold text-white font-bn">{{ __('subscription.plans.heading') }}</h2>
                                <p class="mt-1 text-xs leading-relaxed text-gray-500 font-bn">{{ __('subscription.payment.methods') }}</p>
                            </div>
                        </div>

                        @if($annualSavings > 0)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-[11px] font-bold text-emerald-300 font-bn">
                                <i data-lucide="percent" class="h-3 w-3"></i>
                                {{ __('subscription.billing.save', ['amount' => '৳'.localize_money($annualSavings)]) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-5 grid flex-1 gap-4 sm:grid-cols-2">
                        @forelse($plans as $plan)
                            @php
                                $isYearly = $plan->billing_cycle === \App\Models\SubscriptionPlan::BILLING_CYCLE_YEARLY;
                                $isRecommended = $annualSavings > 0 && $isYearly;
                                $planClasses = $isYearly
                                    ? 'border-emerald-400/30 bg-gradient-to-b from-emerald-500/10 via-emerald-500/5 to-transparent shadow-lg shadow-emerald-950/20'
                                    : 'border-white/10 bg-white/[0.035]';
                            @endphp

                            <article class="relative flex flex-col justify-between rounded-2xl border p-5 transition hover:border-emerald-400/30 {{ $planClasses }}">
                                @if($isRecommended)
                                    <span class="absolute -top-3 right-4 rounded-full bg-emerald-400 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-950 font-bn">
                                        {{ __('subscription.landing.most_popular') }}
                                    </span>
                                @endif

                                <div>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-300 font-bn">
                                        <i data-lucide="{{ $isYearly ? 'calendar-range' : 'calendar-days' }}" class="h-3.5 w-3.5"></i>
                                        {{ localize_number($plan->durationInDays()) }} {{ __('subscription.plans.days') }}
                                    </span>
                                    <h3 class="mt-3 font-bold text-white font-bn">{{ $isYearly ? __('subscription.plans.yearly') : __('subscription.plans.monthly') }}</h3>
                                    <p class="mt-1 flex flex-wrap items-baseline gap-x-2">
                                        <span class="font-display text-2xl font-bold text-white sm:text-3xl">৳{{ localize_money($plan->price) }}</span>
                                        <span class="text-xs text-gray-400 font-bn">{{ $isYearly ? __('subscription.plans.per_year') : __('subscription.plans.per_month') }}</span>
                                    </p>
                                </div>

                                <div class="pt-5">
                                    <form method="POST" action="{{ route('subscriptions.checkout', $product) }}" x-data="{ submitting: false }" @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="plan" value="{{ $plan->slug }}">
                                        <button type="submit" :disabled="submitting" class="btn-emerald flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-60 font-bn">
                                            <span x-show="!submitting">{{ __('subscription.landing.buy_plan') }}</span>
                                            <span x-show="submitting" x-cloak>{{ __('subscription.payment.redirecting') }}</span>
                                        </button>
                                    </form>
                                    <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-[11px] text-gray-500 font-bn">
                                        <i data-lucide="check" class="h-3 w-3 text-emerald-400"></i>
                                        {{ __('subscription.landing.cancel_note', ['days' => localize_number($plan->durationInDays())]) }}
                                    </p>
                                </div>
                            </article>
                        @empty
                            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/5 px-4 py-10 text-center sm:col-span-2">
                                <i data-lucide="credit-card" class="h-7 w-7 text-gray-600"></i>
                                <p class="mt-2 text-sm text-gray-400 font-bn">{{ __('subscription.plans.no_plans') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
@endsection
