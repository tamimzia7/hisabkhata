@extends('layouts.student')

@section('title', __('student.subscription.title'))
@section('heading', __('student.subscription.title'))
@section('subheading', __('student.subscription.subtitle'))

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <div class="glass-strong rounded-2xl p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold font-display font-bn text-white">{{ __('student.subscription.student_plan') }}</h2>
                <p class="mt-1 text-xs text-gray-400 font-bn">{{ __('student.subscription.subtitle') }}</p>
            </div>
            @if($subscription)
                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-[11px] font-bold text-emerald-400 font-bn">{{ __('student.subscription.active') }}</span>
            @else
                <span class="rounded-full bg-orange-500/15 px-3 py-1 text-[11px] font-bold text-orange-400 font-bn">{{ __('student.subscription.inactive') }}</span>
            @endif
        </div>

        @if($subscription)
            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('student.subscription.plan') }}</p>
                    <p class="text-sm font-bold text-white font-bn">{{ $subscription->plan?->name }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('student.subscription.started') }}</p>
                    <p class="text-sm font-bold text-white">{{ $subscription->starts_at?->format('d M Y') }}</p>
                </div>
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="text-[10px] text-gray-500 font-bn">{{ __('student.subscription.expires') }}</p>
                    <p class="text-sm font-bold text-white">{{ $subscription->ends_at?->format('d M Y') }}</p>
                </div>
            </div>
            <a href="{{ route('student.dashboard') }}" class="btn-emerald mt-4 inline-block rounded-lg px-5 py-2.5 text-sm font-bold font-bn">{{ __('student.subscription.open') }}</a>
        @endif
    </div>

    @unless($subscription)
        @forelse($plans as $plan)
            <div class="glass rounded-2xl p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold font-bn text-white">{{ $plan->name }}</h3>
                        <p class="mt-1 text-xs text-gray-400 font-bn">{{ $plan->duration_days }} {{ __('student.subscription.days') }} · {{ __('student.subscription.trial_note') }}</p>
                    </div>
                    <form method="POST" action="{{ route('student.subscription.activate') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan->slug }}">
                        <button type="submit" class="btn-emerald rounded-lg px-5 py-2.5 text-sm font-bold font-bn">{{ __('student.subscription.activate') }}</button>
                    </form>
                </div>
                <ul class="mt-4 grid gap-1.5 text-xs text-gray-300 font-bn sm:grid-cols-2">
                    @foreach(($plan->features['bullets'] ?? []) as $bullet)
                        <li class="flex items-start gap-2"><span class="text-emerald-400">✓</span><span>{{ __($bullet) }}</span></li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="glass rounded-2xl p-6 text-center text-sm text-gray-400 font-bn">{{ __('student.subscription.no_plans') }}</div>
        @endforelse
    @endunless

    <div class="glass rounded-2xl p-4 text-xs text-gray-400 font-bn">
        {{ __('student.subscription.expiry_note') }}
    </div>
</div>
@endsection
