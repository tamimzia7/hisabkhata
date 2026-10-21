@extends('layouts.app')

@section('title', 'রেজিস্ট্রেশন — হিসাবখাতা')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 relative z-10">
    <div class="glass-strong rounded-3xl p-8 md:p-10 w-full max-w-lg glow-green">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <svg width="40" height="40" viewBox="0 0 200 200" fill="none" class="group-hover:scale-105 transition-transform duration-300 drop-shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    <defs>
                        <linearGradient id="logoGreenReg" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#34D399"/><stop offset="100%" stop-color="#047857"/></linearGradient>
                        <linearGradient id="logoOrangeReg" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#EA580C"/></linearGradient>
                    </defs>
                    <path d="M 40 130 Q 100 160 160 130 L 160 60 Q 100 90 40 60 Z" fill="url(#logoGreenReg)"/>
                    <rect x="65" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="119" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="65" y="80" width="70" height="16" rx="5" fill="#047857"/>
                    <circle cx="145" cy="115" r="32" fill="url(#logoOrangeReg)"/><text x="145" y="128" font-family="Inter, sans-serif" font-size="36" font-weight="800" fill="white" text-anchor="middle">৳</text>
                </svg>
                <div>
                    <span class="text-lg font-bold tracking-tight font-bn text-white">হিসাবখাতা</span>
                    <p class="text-[9px] tracking-[0.2em] text-emerald-400 -mt-0.5">SMART FINANCE</p>
                </div>
            </a>
            <a href="{{ route('home') }}" class="text-emerald-300 text-sm font-bn">← হোমে ফিরুন</a>
        </div>
        <h2 class="text-3xl font-bold font-display mb-2 font-bn">{{ __('Register') }}</h2>
        <p class="text-gray-400 text-sm mb-6 font-bn">আপনি কে — সেটাই প্রথমে বলে দিন। বাকিটা আমরা সাজিয়ে নেব।</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ regMode: @js(old('mode', 'student')) }">
            @csrf
            <div>
                <label class="text-sm text-gray-300 font-bn">{{ __('Type') }}</label>
                <div class="grid grid-cols-3 gap-2 mt-2">
                    <button type="button" @click="regMode='student'" :class="regMode==='student' ? 'ring-2 ring-emerald-400 bg-emerald-500/20' : ''" class="glass rounded-xl p-3 text-center transition"><div class="text-2xl mb-1">🎓</div><div class="text-xs font-semibold font-bn">{{ __('Student') }}</div></button>
                    <button type="button" @click="regMode='personal'" :class="regMode==='personal' ? 'ring-2 ring-emerald-400 bg-emerald-500/20' : ''" class="glass rounded-xl p-3 text-center transition"><div class="text-2xl mb-1">🏠</div><div class="text-xs font-semibold font-bn">{{ __('Family') }}</div></button>
                    <button type="button" @click="regMode='business'" :class="regMode==='business' ? 'ring-2 ring-emerald-400 bg-emerald-500/20' : ''" class="glass rounded-xl p-3 text-center transition"><div class="text-2xl mb-1">🏪</div><div class="text-xs font-semibold font-bn">{{ __('Shop') }}</div></button>
                </div>
                <input type="hidden" name="mode" :value="regMode">
                @error('mode')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="name" class="text-sm text-gray-300 font-bn">{{ __('Name') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus placeholder="আপনার নাম"
                    class="w-full mt-1 glass rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50 font-bn">
                @error('name')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="text-sm text-gray-300 font-bn">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="you@example.com"
                    class="w-full mt-1 glass rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                @error('email')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="text-sm text-gray-300 font-bn">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required placeholder="••••••••"
                    class="w-full mt-1 glass rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                @error('password')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-shine btn-green w-full py-3 rounded-lg font-bold font-bn">{{ __('Create free account') }}</button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-sm text-center font-bn"><span class="text-gray-400">{{ __('Have account?') }}</span> <a href="{{ route('login') }}" class="text-emerald-400 font-semibold">{{ __('Login') }}</a></div>
    </div>
</div>
@endsection
