@php
    $status = $activeSubscription
        ? 'active'
        : ($currentSubscription?->status === 'suspended'
            ? 'suspended'
            : ($currentSubscription && $currentSubscription->isExpired() ? 'expired' : 'inactive'));

    $statusClasses = [
        'active' => 'bg-emerald-500/15 text-emerald-400',
        'expired' => 'bg-red-500/15 text-red-400',
        'suspended' => 'bg-amber-500/15 text-amber-400',
        'inactive' => 'bg-orange-500/15 text-orange-400',
    ][$status];

    $statusMessage = match ($status) {
        'active' => __('subscription.current.active_subscription'),
        'suspended' => __('subscription.current.suspended_subscription'),
        default => __('subscription.current.expired_subscription'),
    };

    $paymentStatus = $latestPayment?->status ?? ($currentSubscription?->isTrial() ? 'trial' : null);
    $paymentStatusClasses = match ($paymentStatus) {
        'successful', 'trial' => 'bg-emerald-500/15 text-emerald-400',
        'pending' => 'bg-amber-500/15 text-amber-400',
        'failed', 'cancelled' => 'bg-red-500/15 text-red-400',
        default => 'bg-white/10 text-gray-400',
    };

    $openRoute = $product === \App\Models\SubscriptionPlan::PRODUCT_STUDENT ? route('student.dashboard') : route('dashboard');

    $preferredBilling = request()->query('billing');
    $preferredBilling = in_array($preferredBilling, \App\Models\SubscriptionPlan::PAID_BILLING_CYCLES, true)
        ? $preferredBilling
        : null;
@endphp

<div class="space-y-5">
    <!-- Current status -->
    <div class="glass-strong rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold font-bn text-white">{{ __('subscription.current.heading') }}</h2>
                <p class="mt-1 text-xs text-gray-400 font-bn">{{ $productName }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-[11px] font-bold font-bn {{ $statusClasses }}">
                {{ __('subscription.status.'.$status) }}
            </span>
        </div>

        @if($currentSubscription)
            <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.current.plan') }}</p>
                    <p class="text-sm font-bold text-white font-bn">{{ $currentSubscription->plan?->name ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.current.started') }}</p>
                    <p class="text-sm font-bold text-white">{{ $currentSubscription->starts_at?->format('d M Y') ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.current.expires') }}</p>
                    <p class="text-sm font-bold text-white">{{ $currentSubscription->ends_at?->format('d M Y') ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.current.remaining') }}</p>
                    <p class="text-sm font-bold text-white">{{ localize_number($remainingDays) }} {{ __('subscription.plans.days') }}</p>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-[10px] text-gray-500 font-bn">{{ __('subscription.current.payment_status') }}:</span>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold font-bn {{ $paymentStatusClasses }}">
                    {{ $paymentStatus ? __('subscription.status.'.$paymentStatus) : __('subscription.status.inactive') }}
                </span>
            </div>

            <p class="mt-3 text-xs font-bn {{ $activeSubscription ? 'text-emerald-400' : ($status === 'suspended' ? 'text-amber-400' : 'text-orange-400') }}">
                {{ $statusMessage }}
            </p>

            @if($activeSubscription)
                <a href="{{ $openRoute }}" class="btn-emerald mt-4 inline-block rounded-lg px-5 py-2.5 text-sm font-bold font-bn">{{ __('subscription.open') }}</a>
            @endif
        @else
            <p class="mt-4 text-sm text-gray-400 font-bn">{{ __('subscription.current.no_subscription') }}</p>
        @endif
    </div>

    <!-- Trial -->
    <div class="glass rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-bold font-bn text-white">{{ __('subscription.trial.title') }}</h3>
                <p class="mt-1 text-xs text-gray-400 font-bn">{{ __('subscription.trial.exact_duration') }}</p>
            </div>
            <span class="rounded-full bg-amber-500/15 px-3 py-1 text-[11px] font-bold text-amber-400 font-bn">{{ __('subscription.trial.badge') }}</span>
        </div>

        @if($trial)
            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.trial.start_date') }}</p>
                    <p class="text-sm font-bold text-white">{{ $trial->starts_at->format('d M Y') }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.trial.end_date') }}</p>
                    <p class="text-sm font-bold text-white">{{ $trial->ends_at->format('d M Y') }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('subscription.trial.remaining_days') }}</p>
                    <p class="text-sm font-bold {{ $trial->isActive() ? 'text-emerald-400' : 'text-red-400' }}">{{ localize_number($trialRemainingDays) }} {{ __('subscription.plans.days') }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs font-bn {{ $trial->isActive() ? 'text-emerald-400' : 'text-orange-400' }}">
                {{ $trial->isActive() ? __('subscription.trial.started') : __('subscription.trial.used') }}
            </p>
        @elseif($trialEligible)
            <p class="mt-3 text-sm text-gray-300 font-bn">{{ __('subscription.trial.description', ['product' => $productName]) }}</p>
            <form method="POST" action="{{ route('subscriptions.trial', $product) }}" class="mt-4" onsubmit="this.querySelector('button').disabled = true;">
                @csrf
                <button type="submit" class="btn-emerald rounded-lg px-5 py-2.5 text-sm font-bold font-bn">{{ __('subscription.trial.button') }}</button>
            </form>
        @else
            <p class="mt-3 text-sm text-orange-400 font-bn">{{ __('subscription.trial.not_eligible') }}</p>
        @endif
    </div>

    <!-- Paid plans -->
    <div class="glass rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-bold font-bn text-white">{{ __('subscription.plans.heading') }}</h3>
                <p class="mt-1 text-xs text-gray-400 font-bn">{{ __('subscription.payment.methods') }}</p>
            </div>
        </div>

        @if(!empty($features))
            <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4">
                <p class="text-[10px] uppercase tracking-wide text-gray-500 font-bn">{{ __('subscription.plans.included') }}</p>
                <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                    @foreach($features as $feature)
                        <div class="flex items-start gap-2 text-xs text-gray-300 font-bn">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse($plans as $plan)
                @php
                    $isYearly = $plan->billing_cycle === \App\Models\SubscriptionPlan::BILLING_CYCLE_YEARLY;
                    $isPreferred = $preferredBilling === $plan->billing_cycle;
                    $planClasses = $isYearly ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-white/10 bg-white/5';
                    $planClasses .= $isPreferred ? ' ring-2 ring-emerald-400/70' : '';
                @endphp
                <div class="rounded-xl border {{ $planClasses }} p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-bold font-bn text-white">{{ $isYearly ? __('subscription.plans.yearly') : __('subscription.plans.monthly') }}</h4>
                            <p class="mt-1 text-2xl font-bold font-display text-emerald-400">
                                ৳{{ localize_money($plan->price) }}<span class="text-xs font-normal text-gray-400 font-bn">{{ $isYearly ? __('subscription.plans.per_year') : __('subscription.plans.per_month') }}</span>
                            </p>
                        </div>
                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold text-gray-300 font-bn">{{ localize_number($plan->durationInDays()) }} {{ __('subscription.plans.days') }}</span>
                    </div>

                    <p class="mt-3 text-xs text-gray-400 font-bn">{{ $isYearly ? __('subscription.plans.yearly_description') : __('subscription.plans.monthly_description') }}</p>

                    <form method="POST" action="{{ route('subscriptions.checkout', $product) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan->slug }}">
                        <button type="submit" class="btn-emerald w-full rounded-lg px-4 py-2.5 text-sm font-bold font-bn">{{ __('subscription.plans.pay') }}</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-400 font-bn md:col-span-2">{{ __('subscription.plans.no_plans') }}</p>
            @endforelse
        </div>

        <p class="mt-4 rounded-xl border border-white/10 bg-white/5 p-3 text-xs text-gray-400 font-bn">{{ __('subscription.payment.secure_note') }}</p>
    </div>

    <!-- Payment history -->
    <div class="glass rounded-2xl p-5">
        <h3 class="text-base font-bold font-bn text-white">{{ __('subscription.payment.history') }}</h3>

        @if($payments->isEmpty())
            <p class="mt-3 text-sm text-gray-400 font-bn">{{ __('subscription.payment.no_history') }}</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead class="bg-white/5 text-[10px] uppercase text-gray-400">
                        <tr>
                            <th class="px-3 py-2.5 font-bn">{{ __('subscription.payment.transaction_id') }}</th>
                            <th class="px-3 py-2.5 font-bn">{{ __('subscription.payment.method') }}</th>
                            <th class="px-3 py-2.5 font-bn">{{ __('subscription.payment.amount') }}</th>
                            <th class="px-3 py-2.5 font-bn">{{ __('subscription.payment.date') }}</th>
                            <th class="px-3 py-2.5 font-bn">{{ __('subscription.payment.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($payments as $payment)
                            @php
                                $paymentClass = match ($payment->status) {
                                    'successful' => 'bg-emerald-500/15 text-emerald-400',
                                    'pending' => 'bg-amber-500/15 text-amber-400',
                                    default => 'bg-red-500/15 text-red-400',
                                };
                            @endphp
                            <tr>
                                <td class="px-3 py-2.5 font-mono text-gray-300">
                                    {{ $payment->transaction_id }}
                                    @if($payment->gateway_transaction_id)
                                        <span class="block text-[10px] text-gray-500">{{ $payment->gateway_transaction_id }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 font-bn text-gray-300">{{ $payment->payment_method ?? $payment->gateway }}</td>
                                <td class="px-3 py-2.5 font-bold text-white">৳{{ localize_money($payment->amount) }} <span class="text-[10px] text-gray-500">{{ $payment->currency }}</span></td>
                                <td class="px-3 py-2.5 font-bn text-gray-300">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2.5"><span class="rounded-full px-2 py-1 text-[10px] font-bold font-bn {{ $paymentClass }}">{{ __('subscription.status.'.$payment->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
