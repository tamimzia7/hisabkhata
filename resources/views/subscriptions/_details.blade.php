@php
    $displayStatus = $status ?? 'inactive';

    $statusBadgeClasses = match ($displayStatus) {
        'active' => 'border-emerald-500/25 bg-emerald-500/15 text-emerald-400',
        'trial' => 'border-sky-500/25 bg-sky-500/15 text-sky-400',
        'expired' => 'border-red-500/25 bg-red-500/15 text-red-400',
        'suspended' => 'border-amber-500/25 bg-amber-500/15 text-amber-400',
        'pending' => 'border-orange-500/25 bg-orange-500/15 text-orange-400',
        'cancelled' => 'border-white/10 bg-white/10 text-gray-400',
        default => 'border-white/10 bg-white/5 text-gray-400',
    };

    $paymentStatusClasses = match ($latestPayment?->status) {
        'successful' => 'bg-emerald-500/15 text-emerald-400',
        'pending' => 'bg-amber-500/15 text-amber-400',
        'failed', 'cancelled' => 'bg-red-500/15 text-red-400',
        default => 'bg-white/10 text-gray-400',
    };

    $statusMessage = match ($displayStatus) {
        'expired' => __('subscription.current.expired_subscription'),
        'suspended' => __('subscription.current.suspended_subscription'),
        'cancelled' => __('subscription.current.cancelled_subscription'),
        'inactive' => __('subscription.current.no_subscription'),
        default => null,
    };

    $statusMessageClasses = match ($displayStatus) {
        'expired', 'cancelled' => 'text-red-400',
        'suspended' => 'text-amber-400',
        default => 'text-gray-400',
    };

    $planName = $currentSubscription?->plan?->name;
    $planLabel = $planName ? \Illuminate\Support\Str::after($planName, '— ') : null;

    $openRoute = $product === \App\Models\SubscriptionPlan::PRODUCT_STUDENT
        ? route('student.dashboard')
        : route('dashboard');

    $preferredBilling = request()->query('billing');
    $preferredBilling = in_array($preferredBilling, \App\Models\SubscriptionPlan::PAID_BILLING_CYCLES, true)
        ? $preferredBilling
        : null;

    $trialActive = $trial?->isActive() === true;

    $trialMessage = match (true) {
        $trialActive => __('subscription.trial.started'),
        $trialEligible => __('subscription.trial.eligible'),
        $trial !== null => __('subscription.trial.used'),
        $activeSubscription !== null => __('subscription.trial.unavailable_active'),
        default => __('subscription.trial.not_eligible'),
    };

    $trialMessageClasses = match (true) {
        $trialActive => 'text-emerald-400',
        $trialEligible => 'text-gray-300',
        $trial !== null, $activeSubscription !== null => 'text-gray-400',
        default => 'text-orange-400',
    };
@endphp

<div class="space-y-3">
    {{-- A + B: compact current subscription summary with integrated trial state --}}
    <section class="glass-strong rounded-xl p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h1 class="text-base font-bold font-display text-white sm:text-lg">{{ __('subscription.details.title', ['product' => $productName]) }}</h1>
                <p class="mt-0.5 text-[10px] text-gray-400 font-bn">{{ $audience }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold font-bn {{ $statusBadgeClasses }}">
                    {{ __('subscription.status.'.$displayStatus) }}
                </span>

                @if($activeSubscription)
                    <a href="{{ $openRoute }}" class="btn-emerald rounded-lg px-3 py-1 text-[10px] font-bold font-bn">{{ __('subscription.open') }}</a>
                @endif

                <a href="{{ route('subscriptions.index') }}" class="glass rounded-lg px-3 py-1 text-[10px] text-gray-300 transition hover:bg-white/10 font-bn">{{ __('subscription.view_all') }}</a>
            </div>
        </div>

        <div class="mt-2.5 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="min-w-0 rounded-lg bg-white/5 px-2.5 py-2">
                <p class="text-[9px] text-gray-500 font-bn">{{ __('subscription.current.plan') }}</p>
                <p class="mt-0.5 truncate text-[11px] font-bold text-white font-bn" title="{{ $planName }}">{{ $planLabel ?? '—' }}</p>
            </div>

            <div class="min-w-0 rounded-lg bg-white/5 px-2.5 py-2">
                <p class="text-[9px] text-gray-500 font-bn">{{ __('subscription.current.started') }}</p>
                <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-white">{{ $currentSubscription?->starts_at?->format('d M Y') ?? '—' }}</p>
            </div>

            <div class="min-w-0 rounded-lg bg-white/5 px-2.5 py-2">
                <p class="text-[9px] text-gray-500 font-bn">{{ __('subscription.current.expires') }}</p>
                <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-white">{{ $currentSubscription?->ends_at?->format('d M Y') ?? '—' }}</p>
            </div>

            <div class="min-w-0 rounded-lg bg-white/5 px-2.5 py-2">
                <p class="text-[9px] text-gray-500 font-bn">{{ __('subscription.current.remaining') }}</p>
                <p class="mt-0.5 whitespace-nowrap text-[11px] font-bold text-white">{{ $currentSubscription ? localize_number($remainingDays).' '.__('subscription.plans.days') : '—' }}</p>
            </div>
        </div>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5">
            <div class="flex min-w-0 flex-wrap items-center gap-1.5 text-[10px] font-bn">
                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[9px] font-bold text-amber-400">{{ __('subscription.trial.badge') }}</span>
                <span class="{{ $trialMessageClasses }}">{{ $trialMessage }}</span>

                @if($statusMessage)
                    <span class="text-gray-600">·</span>
                    <span class="{{ $statusMessageClasses }}">{{ $statusMessage }}</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @if($latestPayment)
                    <span class="rounded-full px-2 py-0.5 text-[9px] font-bold font-bn {{ $paymentStatusClasses }}">
                        {{ __('subscription.current.payment_status') }} · {{ __('subscription.status.'.$latestPayment->status) }}
                    </span>
                @endif

                @if($trialEligible)
                    <form method="POST" action="{{ route('subscriptions.trial', $product) }}" onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <button type="submit" class="btn-emerald rounded-lg px-2.5 py-1 text-[10px] font-bold font-bn">{{ __('subscription.trial.button') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <div class="grid gap-3 lg:grid-cols-12">
        {{-- Compact feature panel --}}
        <section class="glass rounded-xl p-3 lg:col-span-5">
            <h2 class="text-sm font-bold font-bn text-white">{{ __('subscription.plans.included') }}</h2>

            <ul class="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                @foreach($features as $feature)
                    <li class="flex items-start gap-1.5 text-[10px] leading-snug text-gray-300 font-bn">
                        <svg class="mt-0.5 h-3 w-3 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- C: compact paid plans --}}
        <section class="glass rounded-xl p-3 lg:col-span-7">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold font-bn text-white">{{ __('subscription.plans.heading') }}</h2>
                    <p class="mt-0.5 text-[10px] text-gray-500 font-bn">{{ __('subscription.payment.methods') }}</p>
                </div>

                @if($annualSavings > 0)
                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[9px] font-bold text-emerald-400 font-bn">
                        {{ __('subscription.billing.save', ['amount' => '৳'.localize_money($annualSavings)]) }}
                    </span>
                @endif
            </div>

            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @forelse($plans as $plan)
                    @php
                        $isYearly = $plan->billing_cycle === \App\Models\SubscriptionPlan::BILLING_CYCLE_YEARLY;
                        $isPreferred = $preferredBilling === $plan->billing_cycle;
                        $planClasses = $isYearly ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-white/10 bg-white/5';
                        $planClasses .= $isPreferred ? ' ring-2 ring-emerald-400/70' : '';
                    @endphp

                    <div class="flex flex-col rounded-lg border {{ $planClasses }} p-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-[11px] font-bold font-bn text-white">{{ $isYearly ? __('subscription.plans.yearly') : __('subscription.plans.monthly') }}</h3>
                            <span class="rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-bold text-gray-300 font-bn">{{ localize_number($plan->durationInDays()) }} {{ __('subscription.plans.days') }}</span>
                        </div>

                        <p class="mt-0.5 flex items-baseline gap-1 text-lg font-bold font-display text-emerald-400">
                            ৳{{ localize_money($plan->price) }}<span class="text-[9px] font-normal text-gray-400 font-bn">{{ $isYearly ? __('subscription.plans.per_year') : __('subscription.plans.per_month') }}</span>
                        </p>

                        <form method="POST" action="{{ route('subscriptions.checkout', $product) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $plan->slug }}">
                            <button type="submit" class="btn-emerald w-full rounded-lg px-3 py-1 text-[10px] font-bold font-bn">{{ __('subscription.plans.pay') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="text-[10px] text-gray-400 font-bn sm:col-span-2">{{ __('subscription.plans.no_plans') }}</p>
                @endforelse
            </div>

            <p class="mt-1.5 text-[9px] leading-snug text-gray-500 font-bn">{{ __('subscription.payment.secure_note_short') }}</p>
        </section>
    </div>

    {{-- Payment history (secondary, compact) --}}
    <section class="glass rounded-xl p-3">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-bold font-bn text-white">{{ __('subscription.payment.history') }}</h2>

            @if($payments->isNotEmpty())
                <span class="rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-bold text-gray-400 font-bn">{{ localize_number($payments->count()) }}</span>
            @endif
        </div>

        @if($payments->isEmpty())
            <p class="mt-1.5 text-[10px] text-gray-400 font-bn">{{ __('subscription.payment.no_history') }}</p>
        @else
            <div class="mt-2 overflow-x-auto lg:max-h-20 lg:overflow-y-auto">
                <table class="w-full min-w-[600px] text-left text-[10px]">
                    <thead class="border-b border-white/10 text-gray-500">
                        <tr>
                            <th class="px-2 py-0 font-bn">{{ __('subscription.payment.transaction_id') }}</th>
                            <th class="px-2 py-0 font-bn">{{ __('subscription.payment.method') }}</th>
                            <th class="px-2 py-0 font-bn">{{ __('subscription.payment.amount') }}</th>
                            <th class="px-2 py-0 font-bn">{{ __('subscription.payment.date') }}</th>
                            <th class="px-2 py-0 font-bn">{{ __('subscription.payment.status') }}</th>
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
                                <td class="px-2 py-0.5 font-mono text-gray-300">
                                    <div class="flex items-center gap-1 whitespace-nowrap">
                                        <span>{{ $payment->transaction_id }}</span>
                                        @if($payment->gateway_transaction_id)
                                            <span class="max-w-[180px] truncate text-[9px] text-gray-500" title="{{ $payment->gateway_transaction_id }}">· {{ $payment->gateway_transaction_id }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-2 py-0.5 font-bn text-gray-300">{{ $payment->payment_method ?? $payment->gateway }}</td>
                                <td class="px-2 py-0.5 font-bold text-white">৳{{ localize_money($payment->amount) }} <span class="text-[9px] text-gray-500">{{ $payment->currency }}</span></td>
                                <td class="px-2 py-0.5 font-bn text-gray-300">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                                <td class="px-2 py-0.5"><span class="rounded-full px-2 py-0 text-[9px] font-bold font-bn {{ $paymentClass }}">{{ __('subscription.status.'.$payment->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
