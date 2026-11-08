@extends('layouts.app')

@section('title', __('subscription.title'))

@section('content')
<div class="min-h-screen flex relative z-10" x-data="{}">
    @include('partials.client-sidebar')

    <main class="flex-1 overflow-x-hidden p-3 md:p-6">
        <div class="mx-auto max-w-5xl">
            <div class="mb-5">
                <p class="text-[10px] uppercase tracking-[0.2em] text-emerald-400">{{ __('site.common.brand') }}</p>
                <h1 class="text-xl font-bold font-display font-bn text-white sm:text-2xl">{{ __('subscription.title') }}</h1>
                <p class="mt-1 text-sm text-gray-400 font-bn">{{ __('subscription.subtitle') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach($products as $item)
                    @php
                        $active = $item['activeSubscription'];
                        $current = $item['currentSubscription'];
                        $badge = $active ? 'active' : ($current?->status === 'suspended' ? 'suspended' : ($current && $current->isExpired() ? 'expired' : 'inactive'));
                        $badgeClass = [
                            'active' => 'bg-emerald-500/15 text-emerald-400',
                            'expired' => 'bg-red-500/15 text-red-400',
                            'suspended' => 'bg-amber-500/15 text-amber-400',
                            'inactive' => 'bg-orange-500/15 text-orange-400',
                        ][$badge];
                    @endphp

                    <div class="glass rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="text-base font-bold font-bn text-white">{{ $item['productName'] }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold font-bn {{ $badgeClass }}">{{ __('subscription.status.'.$badge) }}</span>
                        </div>

                        <div class="mt-3 space-y-1.5 text-xs text-gray-400 font-bn">
                            <p>{{ __('subscription.current.plan') }}: <span class="text-gray-200">{{ $current?->plan?->name ?? '—' }}</span></p>
                            <p>{{ __('subscription.current.expires') }}: <span class="text-gray-200">{{ $current?->ends_at?->format('d M Y') ?? '—' }}</span></p>
                            <p>{{ __('subscription.current.payment_status') }}: <span class="text-gray-200">{{ $item['latestPayment'] ? __('subscription.status.'.$item['latestPayment']->status) : __('subscription.status.inactive') }}</span></p>
                            @if($item['trial'])
                                <p>{{ __('subscription.trial.remaining_days') }}: <span class="text-gray-200">{{ localize_number($item['trialRemainingDays']) }} {{ __('subscription.plans.days') }}</span></p>
                            @elseif($item['trialEligible'])
                                <p class="text-amber-400">{{ __('subscription.trial.eligible') }}</p>
                            @endif
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            @foreach($item['plans'] as $plan)
                                <span class="rounded-lg bg-white/5 px-2 py-2 text-center text-[11px] text-gray-300 font-bn">৳{{ localize_money($plan->price) }} {{ $plan->billing_cycle === \App\Models\SubscriptionPlan::BILLING_CYCLE_YEARLY ? __('subscription.plans.per_year') : __('subscription.plans.per_month') }}</span>
                            @endforeach
                        </div>

                        @if($item['trialEligible'])
                            <p class="mt-3 text-center text-[11px] text-amber-400 font-bn">{{ __('subscription.trial.exact_duration') }}</p>
                        @endif

                        <a href="{{ route('subscriptions.show', $item['product']) }}" class="btn-emerald mt-4 block rounded-lg px-4 py-2.5 text-center text-sm font-bold font-bn">{{ __('subscription.plans.choose') }}</a>
                    </div>
                @endforeach
            </div>
        </div>
    </main>
</div>
@endsection
