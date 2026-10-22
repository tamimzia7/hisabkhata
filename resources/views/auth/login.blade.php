@extends('layouts.app')

@section('title', __('site.auth.login_title'))

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative z-10">
    <div class="glass-strong rounded-2xl sm:rounded-3xl p-5 sm:p-8 md:p-10 w-full max-w-md glow-green">
        <div class="flex items-center justify-between mb-6 gap-2 sm:gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <svg width="40" height="40" viewBox="0 0 200 200" fill="none" class="group-hover:scale-105 transition-transform duration-300 drop-shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    <defs>
                        <linearGradient id="logoGreenLogin" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#34D399"/><stop offset="100%" stop-color="#047857"/></linearGradient>
                        <linearGradient id="logoOrangeLogin" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#EA580C"/></linearGradient>
                    </defs>
                    <path d="M 40 130 Q 100 160 160 130 L 160 60 Q 100 90 40 60 Z" fill="url(#logoGreenLogin)"/>
                    <rect x="65" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="119" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="65" y="80" width="70" height="16" rx="5" fill="#047857"/>
                    <circle cx="145" cy="115" r="32" fill="url(#logoOrangeLogin)"/><text x="145" y="128" font-family="Inter, sans-serif" font-size="36" font-weight="800" fill="white" text-anchor="middle">৳</text>
                </svg>
                <div>
                    <span class="text-base sm:text-lg font-bold tracking-tight font-bn text-white">{{ __('site.common.brand') }}</span>
                    <p class="text-[9px] tracking-[0.2em] text-emerald-400 -mt-0.5">{{ __('site.common.smart_finance') }}</p>
                </div>
            </a>
            @include('partials.language-switcher')
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <h2 class="text-2xl sm:text-3xl font-bold font-display font-bn">{{ __('site.auth.login') }}</h2>
            <a href="{{ route('home') }}" class="text-emerald-300 text-xs sm:text-sm font-bn">{{ __('site.auth.back_home') }}</a>
        </div>
        <p class="text-gray-400 text-sm mb-6 font-bn">{{ __('site.auth.login_subtitle') }}</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="text-sm text-gray-300 font-bn">{{ __('site.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com"
                    class="w-full mt-1 glass rounded-lg px-3 sm:px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                @error('email')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="text-sm text-gray-300 font-bn">{{ __('site.auth.password') }}</label>
                <input id="password" name="password" type="password" required placeholder="••••••••"
                    class="w-full mt-1 glass rounded-lg px-3 sm:px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                @error('password')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-shine btn-green w-full py-3 rounded-lg font-bold font-bn">{{ __('site.auth.login') }}</button>
            <p class="text-xs text-center text-gray-500 font-bn">{{ __('site.auth.secure_note') }}</p>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-sm text-center font-bn"><span class="text-gray-400">{{ __('site.auth.no_account') }}</span> <a href="{{ route('register') }}" class="text-emerald-400 font-semibold">{{ __('site.auth.register_now') }}</a></div>
    </div>
</div>
@endsection
