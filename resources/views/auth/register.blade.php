@extends('layouts.app')

@section('title', 'রেজিস্ট্রেশন — হিসাবখাতা')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 relative z-10">
    <div class="glass-strong rounded-3xl p-8 md:p-10 w-full max-w-lg glow-green">
        <a href="{{ route('home') }}" class="text-emerald-300 text-sm font-bn mb-6 inline-block">← হোমে ফিরুন</a>
        <h2 class="text-3xl font-bold font-display mb-2 font-bn">রেজিস্ট্রেশন</h2>
        <p class="text-gray-400 text-sm mb-6 font-bn">আপনি কে — সেটাই প্রথমে বলে দিন। বাকিটা আমরা সাজিয়ে নেব।</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ regMode: @js(old('mode', 'student')) }">
            @csrf
            <div>
                <label class="text-sm text-gray-300 font-bn">আপনার ধরন</label>
                <div class="grid grid-cols-3 gap-2 mt-2">
                    <button type="button" @click="regMode='student'" :class="regMode==='student' ? 'ring-2 ring-emerald-400 bg-emerald-500/20' : ''" class="glass rounded-xl p-3 text-center transition"><div class="text-2xl mb-1">🎓</div><div class="text-xs font-semibold font-bn">শিক্ষার্থী</div></button>
                    <button type="button" @click="regMode='personal'" :class="regMode==='personal' ? 'ring-2 ring-emerald-400 bg-emerald-500/20' : ''" class="glass rounded-xl p-3 text-center transition"><div class="text-2xl mb-1">🏠</div><div class="text-xs font-semibold font-bn">সংসার</div></button>
                    <button type="button" @click="regMode='business'" :class="regMode==='business' ? 'ring-2 ring-emerald-400 bg-emerald-500/20' : ''" class="glass rounded-xl p-3 text-center transition"><div class="text-2xl mb-1">🏪</div><div class="text-xs font-semibold font-bn">দোকান</div></button>
                </div>
                <input type="hidden" name="mode" :value="regMode">
                @error('mode')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="name" class="text-sm text-gray-300 font-bn">পুরো নাম</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus placeholder="আপনার নাম"
                    class="w-full mt-1 glass rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50 font-bn">
                @error('name')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="text-sm text-gray-300 font-bn">ইমেইল</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="you@example.com"
                    class="w-full mt-1 glass rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                @error('email')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="text-sm text-gray-300 font-bn">পাসওয়ার্ড</label>
                <input id="password" name="password" type="password" required placeholder="••••••••"
                    class="w-full mt-1 glass rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50">
                @error('password')
                    <p class="text-red-400 text-xs mt-1.5 font-bn">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-shine btn-green w-full py-3 rounded-lg font-bold font-bn">ফ্রি অ্যাকাউন্ট খুলুন</button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-sm text-center font-bn"><span class="text-gray-400">আগে থেকেই অ্যাকাউন্ট আছে?</span> <a href="{{ route('login') }}" class="text-emerald-400 font-semibold">লগইন</a></div>
    </div>
</div>
@endsection
