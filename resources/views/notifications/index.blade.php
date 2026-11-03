@extends('layouts.app')

@section('title', __('notifications.title'))

@section('content')
@php($unreadNotificationCount = $counts['unread'])
<div class="min-h-screen flex relative z-10">
    @include('partials.client-sidebar')

    <main class="flex-1 p-3 md:p-6 overflow-x-hidden">
        <header class="max-w-4xl mx-auto glass-strong rounded-2xl p-3 sm:p-4 mb-4 sm:mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
                <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center font-bold text-black shrink-0">৳</div>
                <div class="min-w-0">
                    <div class="text-sm font-bold font-bn truncate">{{ __('site.common.brand') }}</div>
                    <div class="text-[9px] tracking-widest text-emerald-400">DASHBOARD</div>
                </div>
            </a>

            <div class="flex items-center gap-2 shrink-0">
                <x-notifications.bell :count="$counts['unread']" />
                <button @click="toggleDark()" class="w-9 h-9 glass rounded-lg flex items-center justify-center hover:scale-110 transition shrink-0" aria-label="Toggle theme">
                    <span x-show="!dark">🌙</span><span x-show="dark">☀️</span>
                </button>
                @include('partials.language-switcher')
                <form method="POST" action="{{ route('logout') }}" class="md:hidden">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="glass px-3 py-2 rounded-lg text-xs sm:text-sm font-bn text-red-300 hover:bg-red-500/10">
                        {{ __('site.dashboard.logout') }}
                    </button>
                </form>
            </div>
        </header>

        <div class="max-w-4xl mx-auto">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-emerald-300 hover:text-emerald-200 font-bn mb-4">
                ← {{ __('notifications.back_to_dashboard') }}
            </a>

            @include('notifications._list')
        </div>
    </main>
</div>
@endsection
