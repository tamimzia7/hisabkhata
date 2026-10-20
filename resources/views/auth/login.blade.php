@extends('layouts.app')

@section('title', 'লগইন — হিসাবখাতা')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 relative z-10">
    <div class="glass-strong rounded-3xl p-8 md:p-10 w-full max-w-md glow-green">
        <a href="{{ route('home') }}" class="text-emerald-300 text-sm font-bn mb-6 inline-block">← হোমে ফিরুন</a>
        <h2 class="text-3xl font-bold font-display mb-2 font-bn">লগইন</h2>
        <p class="text-gray-400 text-sm mb-6 font-bn">আপনার অ্যাকাউন্টে প্রবেশ করুন</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="text-sm text-gray-300 font-bn">ইমেইল</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com"
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
            <button type="submit" class="btn-shine btn-green w-full py-3 rounded-lg font-bold font-bn">লগইন</button>
            <p class="text-xs text-center text-gray-500 font-bn">নিরাপদ লগইন — প্রতি মিনিটে সর্বোচ্চ ৫ বার চেষ্টা করা যাবে।</p>
        </form>

        <div class="mt-6 pt-6 border-t border-white/10 text-sm text-center font-bn"><span class="text-gray-400">অ্যাকাউন্ট নেই?</span> <a href="{{ route('register') }}" class="text-emerald-400 font-semibold">রেজিস্টার করুন</a></div>
    </div>
</div>
@endsection
