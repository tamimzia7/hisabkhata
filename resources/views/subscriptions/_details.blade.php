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

    $statusIcon = match ($displayStatus) {
        'active' => 'check-circle',
        'trial' => 'gift',
        'expired' => 'alert-circle',
        'suspended' => 'pause-circle',
        'pending' => 'clock',
        'cancelled' => 'x-circle',
        default => 'circle',
    };

    $statusIconClasses = match ($displayStatus) {
        'active' => 'bg-emerald-500/15 text-emerald-400',
        'trial' => 'bg-sky-500/15 text-sky-400',
        'expired' => 'bg-red-500/15 text-red-400',
        'suspended' => 'bg-amber-500/15 text-amber-400',
        'pending' => 'bg-orange-500/15 text-orange-400',
        default => 'bg-white/10 text-gray-400',
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

    $statusMessageContainerClasses = match ($displayStatus) {
        'expired', 'cancelled' => 'border-red-500/20 bg-red-500/10',
        'suspended' => 'border-amber-500/20 bg-amber-500/10',
        default => 'border-white/10 bg-white/5',
    };

    $planName = $currentSubscription?->plan?->name;
    $planLabel = $planName ? \Illuminate\Support\Str::after($planName, '— ') : null;

    $openRoute = route('student.dashboard');

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
        $trialActive => 'text-emerald-300',
        $trialEligible => 'text-gray-300',
        $trial !== null, $activeSubscription !== null => 'text-gray-400',
        default => 'text-orange-400',
    };

    $recommendedBilling = $preferredBilling
        ?? ($annualSavings > 0 ? \App\Models\SubscriptionPlan::BILLING_CYCLE_YEARLY : null);

    $featureIcons = match ($product) {
        \App\Models\SubscriptionPlan::PRODUCT_STUDENT => ['users', 'utensils', 'shopping-cart', 'wallet', 'file-text'],
        default => ['check', 'check', 'check', 'check', 'check'],
    };
@endphp

<style>
    html body .subscription-details,
    html body .subscription-details * {
        font-weight: 400 !important;
    }

    html body .subscription-details .font-display,
    html body .subscription-details .font-bold {
        font-weight: 700 !important;
    }

    html body .subscription-details .subscription-price {
        font-weight: 800 !important;
        letter-spacing: -0.04em;
    }
</style>

<div class="subscription-details space-y-5">
    <section class="relative overflow-hidden rounded-3xl border border-emerald-400/20 bg-gradient-to-br from-emerald-500/15 via-emerald-950/20 to-transparent p-5 shadow-2xl shadow-emerald-950/30 sm:p-6">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-emerald-400/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-teal-400/10 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/25 bg-emerald-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-300 font-bn">
                        <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                        {{ __('subscription.title') }}
                    </span>
                    <span class="rounded-full border px-3 py-1 text-[11px] font-bold font-bn {{ $statusBadgeClasses }}">
                        {{ __('subscription.status.'.$displayStatus) }}
                    </span>
                    @if($latestPayment)
                        <span class="rounded-full px-3 py-1 text-[11px] font-bold font-bn {{ $paymentStatusClasses }}">
                            {{ __('subscription.current.payment_status') }} · {{ __('subscription.status.'.$latestPayment->status) }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-2xl font-display text-white sm:text-3xl">{{ __('subscription.details.title', ['product' => $productName]) }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-300 font-bn">{{ $audience }}</p>
                <p class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500 font-bn">{{ $description ?? '' }}</p>
            </div>

            <div class="flex flex-wrap gap-2.5">
                @if($activeSubscription)
                    <a href="{{ $openRoute }}" class="btn-emerald inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold font-bn">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        {{ __('subscription.open') }}
                    </a>
                @endif

                <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-bold text-gray-200 transition hover:border-emerald-400/20 hover:bg-white/10 font-bn">
                    <i data-lucide="credit-card" class="h-4 w-4"></i>
                    {{ __('subscription.view_all') }}
                </a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="glass col-span-2 flex min-h-[8.5rem] flex-col justify-between rounded-2xl p-4 sm:p-5 lg:col-span-1">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-400">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </span>
            <div class="mt-4">
                <p class="text-xs text-gray-400 font-bn">{{ __('subscription.current.plan') }}</p>
                <p class="mt-1 truncate text-lg font-bold text-white font-bn" title="{{ $planName }}">{{ $planLabel ?? '—' }}</p>
            </div>
        </div>

        <div class="glass flex min-h-[8.5rem] flex-col justify-between rounded-2xl p-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $statusIconClasses }}">
                <i data-lucide="{{ $statusIcon }}" class="h-5 w-5"></i>
            </span>
            <div class="mt-4">
                <p class="text-xs text-gray-400 font-bn">{{ __('subscription.current.status') }}</p>
                <span class="mt-1 inline-flex rounded-full border px-2.5 py-1 text-[11px] font-bold font-bn {{ $statusBadgeClasses }}">
                    {{ __('subscription.status.'.$displayStatus) }}
                </span>
            </div>
        </div>

        <div class="glass flex min-h-[8.5rem] flex-col justify-between rounded-2xl p-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/15 text-sky-400">
                <i data-lucide="calendar" class="h-5 w-5"></i>
            </span>
            <div class="mt-4">
                <p class="text-xs text-gray-400 font-bn">{{ __('subscription.current.started') }}</p>
                <p class="mt-1 whitespace-nowrap text-base font-bold text-white sm:text-lg">{{ $currentSubscription?->starts_at?->format('d M Y') ?? '—' }}</p>
            </div>
        </div>

        <div class="glass flex min-h-[8.5rem] flex-col justify-between rounded-2xl p-4 sm:p-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-400">
                <i data-lucide="calendar-clock" class="h-5 w-5"></i>
            </span>
            <div class="mt-4">
                <p class="text-xs text-gray-400 font-bn">{{ __('subscription.current.expires') }}</p>
                <p class="mt-1 whitespace-nowrap text-base font-bold text-white sm:text-lg">{{ $currentSubscription?->ends_at?->format('d M Y') ?? '—' }}</p>
            </div>
        </div>

        <div class="glass col-span-2 flex min-h-[8.5rem] flex-col justify-between rounded-2xl p-4 sm:p-5 lg:col-span-1">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/15 text-teal-400">
                <i data-lucide="clock" class="h-5 w-5"></i>
            </span>
            <div class="mt-4">
                <p class="text-xs text-gray-400 font-bn">{{ __('subscription.current.remaining') }}</p>
                <p class="mt-1 whitespace-nowrap text-base font-bold text-white sm:text-lg">{{ $currentSubscription ? localize_number($remainingDays).' '.__('subscription.plans.days') : '—' }}</p>
            </div>
        </div>
    </section>

    @if($statusMessage)
        <div class="flex items-start gap-3 rounded-2xl border px-4 py-3.5 {{ $statusMessageContainerClasses }}">
            <i data-lucide="{{ $statusIcon }}" class="mt-0.5 h-4 w-4 shrink-0 {{ $statusMessageClasses }}"></i>
            <p class="text-sm leading-relaxed font-bn {{ $statusMessageClasses }}">{{ $statusMessage }}</p>
        </div>
    @endif

    <section class="glass rounded-2xl p-4 sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-300">
                    <i data-lucide="gift" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-bold text-white font-bn">{{ __('subscription.trial.title') }}</h2>
                        <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-[10px] font-bold text-amber-300 font-bn">{{ __('subscription.trial.badge') }}</span>
                    </div>
                    <p class="mt-1 text-sm leading-relaxed font-bn {{ $trialMessageClasses }}">{{ $trialMessage }}</p>

                    @if($trialActive && $trial)
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 font-bn">
                            <span>{{ __('subscription.trial.start_date') }}: <span class="font-bold text-gray-300">{{ $trial->starts_at?->format('d M Y') ?? '—' }}</span></span>
                            <span>{{ __('subscription.trial.end_date') }}: <span class="font-bold text-gray-300">{{ $trial->ends_at?->format('d M Y') ?? '—' }}</span></span>
                            <span>{{ __('subscription.trial.remaining_days') }}: <span class="font-bold text-gray-300">{{ localize_number($trial->remainingDays()) }} {{ __('subscription.plans.days') }}</span></span>
                        </div>
                    @endif
                </div>
            </div>

            @if($trialEligible)
                <form method="POST" action="{{ route('subscriptions.trial', $product) }}" x-data="{ submitting: false }" @submit="submitting = true" class="shrink-0">
                    @csrf
                    <button type="submit" :disabled="submitting" class="btn-emerald flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold font-bn transition disabled:cursor-not-allowed disabled:opacity-60 lg:w-auto">
                        <span x-show="!submitting" class="inline-flex items-center gap-2">
                            <i data-lucide="gift" class="h-4 w-4"></i>
                            {{ __('subscription.trial.button') }}
                        </span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            {{ __('subscription.trial.activating') }}
                        </span>
                    </button>
                </form>
            @endif
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-12">
        <section class="glass rounded-3xl p-5 xl:col-span-5">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-400">
                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-white font-bn">{{ __('subscription.plans.included') }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-gray-500 font-bn">{{ $description ?? '' }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                @foreach($features as $feature)
                    <div class="group flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-3.5 transition hover:border-emerald-400/25 hover:bg-emerald-500/5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 transition group-hover:bg-emerald-500/15">
                            <i data-lucide="{{ $featureIcons[$loop->index] ?? 'check' }}" class="h-4 w-4"></i>
                        </span>
                        <p class="pt-1 text-sm leading-snug text-gray-200 font-bn">{{ $feature }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="glass rounded-3xl p-5 xl:col-span-7">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-400">
                        <i data-lucide="credit-card" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-white font-bn">{{ __('subscription.plans.heading') }}</h2>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500 font-bn">{{ __('subscription.payment.methods') }}</p>
                    </div>
                </div>

                @if($annualSavings > 0)
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-1 text-[11px] font-bold text-emerald-300 font-bn">
                        <i data-lucide="piggy-bank" class="h-3.5 w-3.5"></i>
                        {{ __('subscription.billing.save', ['amount' => '৳'.localize_money($annualSavings)]) }}
                    </span>
                @endif
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @forelse($plans as $plan)
                    @php
                        $isYearly = $plan->billing_cycle === \App\Models\SubscriptionPlan::BILLING_CYCLE_YEARLY;
                        $isRecommended = $recommendedBilling === $plan->billing_cycle;
                        $planClasses = $isYearly
                            ? 'border-emerald-400/30 bg-gradient-to-b from-emerald-500/10 via-emerald-500/5 to-transparent'
                            : 'border-white/10 bg-white/[0.035]';
                        $planClasses .= $isRecommended ? ' ring-2 ring-emerald-400/40 shadow-xl shadow-emerald-950/20' : '';
                    @endphp

                    <article class="relative flex flex-col overflow-hidden rounded-3xl border p-5 transition hover:-translate-y-0.5 hover:border-emerald-400/30 {{ $planClasses }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-300 font-bn">
                                <i data-lucide="{{ $isYearly ? 'calendar-range' : 'calendar-days' }}" class="h-3.5 w-3.5"></i>
                                {{ localize_number($plan->durationInDays()) }} {{ __('subscription.plans.days') }}
                            </span>

                            @if($isRecommended)
                                <span class="rounded-full bg-emerald-400 px-2.5 py-1 text-[10px] font-bold text-emerald-950 font-bn">{{ __('subscription.plans.recommended') }}</span>
                            @endif
                        </div>

                        <h3 class="mt-3 text-lg font-bold text-white font-bn">{{ $isYearly ? __('subscription.plans.yearly') : __('subscription.plans.monthly') }}</h3>

                        <p class="mt-1 flex flex-wrap items-baseline gap-x-2">
                            <span class="subscription-price text-3xl font-display text-white sm:text-4xl">৳{{ localize_money($plan->price) }}</span>
                            <span class="text-sm text-gray-400 font-bn">{{ $isYearly ? __('subscription.plans.per_year') : __('subscription.plans.per_month') }}</span>
                        </p>

                        <p class="mt-1 text-xs leading-relaxed text-gray-400 font-bn">{{ $isYearly ? __('subscription.plans.yearly_description') : __('subscription.plans.monthly_description') }}</p>

                        @if($isYearly && $annualSavings > 0)
                            <p class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-1 text-[11px] font-bold text-emerald-300 font-bn">
                                <i data-lucide="piggy-bank" class="h-3.5 w-3.5"></i>
                                {{ __('subscription.billing.save', ['amount' => '৳'.localize_money($annualSavings)]) }}
                            </p>
                        @else
                            <p class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-white/5 px-2.5 py-1 text-[11px] text-gray-400 font-bn">
                                <i data-lucide="calendar-check" class="h-3.5 w-3.5"></i>
                                {{ $isYearly ? __('subscription.billing.billed_yearly') : __('subscription.billing.billed_monthly') }}
                            </p>
                        @endif

                        <div class="mt-4 rounded-2xl border border-white/10 bg-black/10 p-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-gray-500 font-bn">{{ __('subscription.plans.included') }}</p>
                            <ul class="mt-2.5 space-y-2">
                                @foreach($features as $feature)
                                    <li class="flex items-start gap-2 text-xs leading-snug text-gray-300 font-bn">
                                        <i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-400"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('subscriptions.checkout', $product) }}" x-data="{ submitting: false }" @submit="submitting = true" class="mt-auto pt-4">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $plan->slug }}">
                            <button type="submit" :disabled="submitting" class="btn-emerald flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold font-bn transition disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="!submitting" class="inline-flex items-center gap-2">
                                    <i data-lucide="credit-card" class="h-4 w-4"></i>
                                    {{ __('subscription.plans.pay') }}
                                </span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    {{ __('subscription.payment.redirecting') }}
                                </span>
                            </button>
                        </form>
                    </article>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/5 px-4 py-10 text-center sm:col-span-2">
                        <i data-lucide="credit-card" class="h-7 w-7 text-gray-600"></i>
                        <p class="mt-2 text-sm text-gray-400 font-bn">{{ __('subscription.plans.no_plans') }}</p>
                    </div>
                @endforelse
            </div>

            <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-gray-500 font-bn">
                <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                <span>{{ __('subscription.payment.secure_note_short') }}</span>
            </p>
        </section>
    </div>

    <section class="glass rounded-3xl p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-400">
                    <i data-lucide="receipt" class="h-5 w-5"></i>
                </span>
                <h2 class="text-lg font-bold text-white font-bn">{{ __('subscription.payment.history') }}</h2>
            </div>

            @if($payments->isNotEmpty())
                <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold text-gray-400 font-bn">{{ localize_number($payments->count()) }}</span>
            @endif
        </div>

        @if($payments->isEmpty())
            <div class="mt-4 flex flex-col items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/5 px-4 py-10 text-center">
                <i data-lucide="receipt-text" class="h-7 w-7 text-gray-600"></i>
                <p class="mt-2 text-sm text-gray-400 font-bn">{{ __('subscription.payment.no_history') }}</p>
            </div>
        @else
            <div class="mt-4 max-h-80 overflow-auto rounded-2xl border border-white/10">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="sticky top-0 bg-emerald-500/10 text-[10px] uppercase tracking-[0.12em] text-gray-400 backdrop-blur">
                        <tr>
                            <th class="px-3 py-2.5 font-bold font-bn">{{ __('subscription.payment.transaction_id') }}</th>
                            <th class="px-3 py-2.5 font-bold font-bn">{{ __('subscription.payment.method') }}</th>
                            <th class="px-3 py-2.5 font-bold font-bn">{{ __('subscription.payment.amount') }}</th>
                            <th class="px-3 py-2.5 font-bold font-bn">{{ __('subscription.payment.date') }}</th>
                            <th class="px-3 py-2.5 font-bold font-bn">{{ __('subscription.payment.status') }}</th>
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
                            <tr class="transition hover:bg-white/5">
                                <td class="px-3 py-2.5 font-mono text-xs text-gray-300">
                                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                                        <span>{{ $payment->transaction_id }}</span>
                                        @if($payment->gateway_transaction_id)
                                            <span class="max-w-[180px] truncate text-[10px] text-gray-500" title="{{ $payment->gateway_transaction_id }}">· {{ $payment->gateway_transaction_id }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-gray-300 font-bn">{{ $payment->payment_method ?? $payment->gateway }}</td>
                                <td class="px-3 py-2.5 text-xs font-bold whitespace-nowrap text-white">৳{{ localize_money($payment->amount) }} <span class="text-[10px] font-normal text-gray-500">{{ $payment->currency }}</span></td>
                                <td class="px-3 py-2.5 text-xs whitespace-nowrap text-gray-300 font-bn">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2.5"><span class="rounded-full px-2.5 py-1 text-[10px] font-bold font-bn {{ $paymentClass }}">{{ __('subscription.status.'.$payment->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
