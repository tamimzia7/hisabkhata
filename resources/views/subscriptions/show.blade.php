@extends('layouts.app')

@section('title', $productName.' — '.__('subscription.title'))

@section('content')
<div class="min-h-screen flex relative z-10" x-data="{}">
    @include('partials.client-sidebar')

    <main class="flex-1 overflow-x-hidden p-3 md:p-6">
        <div class="mx-auto max-w-4xl">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.2em] text-emerald-400">{{ __('site.common.brand') }}</p>
                    <h1 class="text-xl font-bold font-display font-bn text-white sm:text-2xl">{{ $productName }}</h1>
                    <p class="mt-1 text-sm text-gray-400 font-bn">{{ __('subscription.subtitle') }}</p>
                </div>
                <a href="{{ route('subscriptions.index') }}" class="glass rounded-lg px-4 py-2 text-xs text-gray-300 transition hover:bg-white/10 font-bn">{{ __('subscription.view_all') }}</a>
            </div>

            @include('subscriptions._details')
        </div>
    </main>
</div>
@endsection
