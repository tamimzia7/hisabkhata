@extends('layouts.app')

@section('content')
<div>
    <!-- NAVBAR -->
    <nav class="sticky top-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <svg width="44" height="44" viewBox="0 0 200 200" fill="none" class="group-hover:scale-105 transition-transform duration-300 drop-shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    <defs>
                        <linearGradient id="logoGreen" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#34D399"/><stop offset="100%" stop-color="#047857"/></linearGradient>
                        <linearGradient id="logoOrange" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#EA580C"/></linearGradient>
                    </defs>
                    <path d="M 40 130 Q 100 160 160 130 L 160 60 Q 100 90 40 60 Z" fill="url(#logoGreen)"/>
                    <rect x="65" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="119" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="65" y="80" width="70" height="16" rx="5" fill="#047857"/>
                    <circle cx="145" cy="115" r="32" fill="url(#logoOrange)"/><text x="145" y="128" font-family="Inter, sans-serif" font-size="36" font-weight="800" fill="white" text-anchor="middle">৳</text>
                </svg>
                <div>
                    <span class="text-xl font-bold tracking-tight font-bn text-white">হিসাবখাতা</span>
                    <p class="text-[10px] tracking-[0.2em] text-emerald-400 -mt-0.5">SMART FINANCE</p>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-1">
                <a href="#showcase" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">ভেতরে কী আছে</a>
                <a href="#features" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">ফিচার</a>
                <a href="#compare" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">তুলনা</a>
                <a href="#pricing" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">দাম</a>
                <a href="#faq" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">প্রশ্নোত্তর</a>
            </div>
            <div class="hidden md:flex items-center gap-3">
                <button @click="dark = !dark" class="w-10 h-10 glass rounded-full flex items-center justify-center hover:scale-110 transition"><span x-show="!dark">🌙</span><span x-show="dark">☀️</span></button>
                @php $currentLocale = app()->getLocale(); @endphp
                <div class="flex items-center gap-1 glass rounded-full px-2 py-1">
                    <a href="{{ route('lang.switch', 'bn') }}" class="px-2.5 py-1 text-xs font-bn rounded-full transition {{ $currentLocale === 'bn' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}">বাংলা</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="px-2.5 py-1 text-xs font-bn rounded-full transition {{ $currentLocale === 'en' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}">EN</a>
                </div>
                @guest
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-300 hover:text-white px-4 py-2 font-bn">{{ __('Login') }}</a>
                    <a href="{{ route('register') }}" class="btn-shine btn-green text-sm font-bold px-6 py-2.5 rounded-full font-bn">ফ্রি শুরু →</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-shine btn-green text-sm font-bold px-6 py-2.5 rounded-full font-bn">ড্যাশবোর্ড →</a>
                @endguest
            </div>
            <button @click="mobileMenu = !mobileMenu" class="md:hidden glass w-10 h-10 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <div x-show="mobileMenu" x-transition class="md:hidden px-6 pb-4 space-y-2 border-t border-emerald-500/10">
            <a href="#showcase" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">ভেতরে কী আছে</a>
            <a href="#features" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">ফিচার</a>
            <a href="#pricing" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">দাম</a>
            @guest
                <a href="{{ route('register') }}" @click="mobileMenu=false" class="block text-center btn-green py-2.5 rounded-lg font-bold mt-2 font-bn">ফ্রি শুরু</a>
            @else
                <a href="{{ route('dashboard') }}" @click="mobileMenu=false" class="block text-center btn-green py-2.5 rounded-lg font-bold mt-2 font-bn">ড্যাশবোর্ড</a>
            @endguest
        </div>
    </nav>

    <!-- HERO -->
    <section class="relative pt-20 pb-16 md:pt-28 md:pb-24 z-10">
        <div class="relative max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 glass rounded-full px-5 py-2 text-sm font-medium mb-8 glow-green">
                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                        <span class="text-emerald-200 font-bn">প্রথম মাস পুরোটাই ফ্রি — কার্ড লাগবে না</span>
                    </div>
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-display leading-[1.05] mb-6 font-bn">
                        টাকার <span class="gradient-shimmer">হিসাব</span><br><span class="text-white">সহজ, পরিষ্কার, নিশ্চিন্ত</span><span class="cursor"></span>
                    </h1>
                    <p class="text-lg md:text-xl text-gray-300 mb-10 leading-relaxed max-w-lg font-bn">মেসের মিল, সংসারের খরচ, দোকানের বাকি — <span class="text-emerald-300">সব এক অ্যাপে।</span> হিসাব রাখা এত সহজ আর কখনো হয়নি।</p>
                    <div class="flex flex-col sm:flex-row gap-4 mb-10">
                        <a href="{{ route('register') }}" class="btn-shine btn-green group px-8 py-4 font-bold rounded-full text-center flex items-center justify-center gap-2 font-bn">ফ্রি অ্যাকাউন্ট খুলুন <span class="group-hover:translate-x-1 transition">→</span></a>
                        <a href="#showcase" class="glass px-8 py-4 font-bold rounded-full hover:bg-white/10 transition text-center flex items-center justify-center gap-2 font-bn">
                            <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8A1 1 0 004.8 3.6v12.8a1 1 0 001.5.8l10.4-6.4a1 1 0 000-1.6L6.3 2.8z"/></svg> ভেতরে কী আছে
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center gap-6 text-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex -space-x-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 border-2 border-[#06130D]"></div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 border-2 border-[#06130D]"></div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-lime-400 to-lime-600 border-2 border-[#06130D]"></div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 border-2 border-[#06130D] flex items-center justify-center text-white text-xs font-bold">+</div>
                            </div>
                            <span class="text-gray-300 font-bn"><strong class="text-white">৫,০০০+</strong> জন ব্যবহার করছেন</span>
                        </div>
                        <div class="h-4 w-px bg-white/10"></div>
                        <div class="flex items-center gap-1 text-amber-400">⭐⭐⭐⭐⭐ <span class="text-gray-300 ml-2 font-bn">৪.৯/৫ রেটিং</span></div>
                    </div>
                </div>
                <div class="relative">
                    <div class="relative glass-strong rounded-3xl overflow-hidden glow-green">
                        <div class="bg-white/5 px-5 py-3 flex items-center gap-2 border-b border-emerald-500/10">
                            <div class="w-3 h-3 bg-red-400/70 rounded-full"></div><div class="w-3 h-3 bg-yellow-400/70 rounded-full"></div><div class="w-3 h-3 bg-green-400/70 rounded-full"></div>
                            <div class="ml-4 flex-1 bg-white/5 rounded-lg px-4 py-1.5 text-xs text-gray-400 text-center">hisabkhata.com/dashboard</div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-3 gap-3 mb-5">
                                <div class="glass p-3 rounded-xl"><p class="text-xs text-gray-400 mb-1 font-bn">মোট মিল</p><p class="text-2xl font-bold font-display">১৪২</p></div>
                                <div class="glass p-3 rounded-xl"><p class="text-xs text-gray-400 mb-1 font-bn">মিল রেট</p><p class="text-2xl font-bold font-display text-emerald-400">৳৪২.৫</p></div>
                                <div class="glass p-3 rounded-xl"><p class="text-xs text-gray-400 mb-1 font-bn">ব্যালেন্স</p><p class="text-2xl font-bold font-display text-orange-400">+৳৮৫০</p></div>
                            </div>
                            <div class="glass rounded-xl p-4">
                                <div class="flex items-center justify-between mb-3"><h4 class="text-sm font-semibold font-bn">এই মাসের আয়-ব্যয়</h4><span class="text-xs text-emerald-400 font-bn">নভেম্বর</span></div>
                                <canvas id="heroChart" height="70"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 -left-6 glass-strong rounded-2xl p-4 flex items-center gap-3 float glow-green">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-2xl">💰</div>
                        <div><p class="text-sm font-bold font-bn">৳৪৯/মাসে</p><p class="text-xs text-gray-400 font-bn">সব সুবিধা</p></div>
                    </div>
                    <div class="absolute -top-4 -right-4 glass-strong rounded-2xl p-3 flex items-center gap-2 float glow-green">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center text-lg">🎓</div>
                        <div><p class="text-xs font-bold font-bn">মেস মোড</p><p class="text-xs text-gray-400 font-bn">অটো হিসাব</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="relative py-10 z-10"><div class="max-w-7xl mx-auto px-6"><div class="glass-strong rounded-2xl p-6 md:p-8 glow-green">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div><p class="text-3xl md:text-4xl font-bold font-display text-emerald-400">৫,০০০+</p><p class="text-sm text-gray-400 mt-1 font-bn">সক্রিয় ব্যবহারকারী</p></div>
            <div><p class="text-3xl md:text-4xl font-bold font-display text-teal-400">২,৫০,০০০+</p><p class="text-sm text-gray-400 mt-1 font-bn">মোট এন্ট্রি</p></div>
            <div><p class="text-3xl md:text-4xl font-bold font-display text-lime-400">৯৯.৯%</p><p class="text-sm text-gray-400 mt-1 font-bn">সার্ভার আপটাইম</p></div>
            <div><p class="text-3xl md:text-4xl font-bold font-display text-orange-400">৪.৯/৫</p><p class="text-sm text-gray-400 mt-1 font-bn">ব্যবহারকারীর রেটিং</p></div>
        </div>
    </div></div></section>

    <!-- MARQUEE -->
    <section class="py-6 border-y border-emerald-500/10 overflow-hidden relative z-10">
        <div class="flex marquee whitespace-nowrap gap-16 text-gray-500 font-medium text-base font-bn">
            <span>🎓 ১,২০০+ শিক্ষার্থী</span><span class="text-emerald-500/50">◆</span><span>🏪 ৫০০+ দোকান</span><span class="text-emerald-500/50">◆</span><span>🏠 ৩,০০০+ পরিবার</span><span class="text-emerald-500/50">◆</span><span>⭐ ৪.৮/৫ রেটিং</span><span class="text-emerald-500/50">◆</span><span>🇧🇩 পুরোটাই বাংলায়</span><span class="text-emerald-500/50">◆</span><span>🔒 শতভাগ নিরাপদ</span><span class="text-emerald-500/50">◆</span>
            <span>🎓 ১,২০০+ শিক্ষার্থী</span><span class="text-emerald-500/50">◆</span><span>🏪 ৫০০+ দোকান</span><span class="text-emerald-500/50">◆</span><span>🏠 ৩,০০০+ পরিবার</span><span class="text-emerald-500/50">◆</span><span>⭐ ৪.৮/৫ রেটিং</span>
        </div>
    </section>

    <!-- SHOWCASE -->
    <section id="showcase" class="py-24 relative z-10" x-data="{ showcaseTab: 'student' }">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block glass text-emerald-300 text-xs font-semibold px-4 py-1.5 rounded-full mb-5 tracking-[0.2em] font-bn">ভেতরে কী আছে</span>
                <h2 class="text-3xl md:text-5xl font-display mb-4 font-bn">আপনার জন্য <span class="gradient-green">যেভাবে সাজানো</span></h2>
                <p class="text-gray-400 text-lg font-bn">যে কাজেই ব্যবহার করুন — ড্যাশবোর্ড নিজেই বদলে যায়</p>
            </div>
            <div class="flex justify-center gap-2 mb-10 flex-wrap">
                <button @click="showcaseTab = 'student'" :class="showcaseTab === 'student' ? 'tab-btn active' : 'glass'" class="px-6 py-3 rounded-full font-semibold text-sm transition-all hover:scale-105 font-bn">🎓 শিক্ষার্থী</button>
                <button @click="showcaseTab = 'personal'" :class="showcaseTab === 'personal' ? 'tab-btn active' : 'glass'" class="px-6 py-3 rounded-full font-semibold text-sm transition-all hover:scale-105 font-bn">🏠 সংসার</button>
                <button @click="showcaseTab = 'business'" :class="showcaseTab === 'business' ? 'tab-btn active' : 'glass'" class="px-6 py-3 rounded-full font-semibold text-sm transition-all hover:scale-105 font-bn">🏪 দোকান</button>
            </div>
            <div class="glass-strong rounded-3xl p-6 md:p-10 glow-green">
                <div x-show="showcaseTab === 'student'" x-transition class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-3xl font-bold font-display mb-4 font-bn">🎓 মেসের পুরো হিসাব</h3>
                        <p class="text-gray-400 mb-6 font-bn">রুমমেট কয়জন, কে কত বাজার করলো, কে কত টাকা জমা দিলো — সব এক জায়গায়। মিল রেট নিজে থেকেই হিসাব হয়ে যায়।</p>
                        <ul class="space-y-3 text-sm font-bn">
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs">✓</span> কে কত টাকা পাবে, কে দেবে — এক নজরে</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs">✓</span> প্রতিদিনের মিল কাউন্ট</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs">✓</span> বাজারের খরচ ট্র্যাকিং</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6">
                        <h4 class="text-sm font-semibold mb-4 text-emerald-300 font-bn">মেম্বারদের ব্যালেন্স</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full"></div><span class="text-sm font-medium font-bn">রাফি</span></div><span class="text-sm font-bold text-emerald-400 font-bn">+৳৯৬০</span></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-teal-400 to-teal-600 rounded-full"></div><span class="text-sm font-medium font-bn">সাকিব</span></div><span class="text-sm font-bold text-emerald-400 font-bn">+৳৫৪৫</span></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full"></div><span class="text-sm font-medium font-bn">তানভীর</span></div><span class="text-sm font-bold text-red-400 font-bn">−৳৪০</span></div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/10 flex justify-between text-sm font-bn"><span class="text-gray-400">মিল রেট</span><span class="font-bold text-emerald-400">৳৪২.৫০</span></div>
                    </div>
                </div>
                <div x-show="showcaseTab === 'personal'" x-transition class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-3xl font-bold font-display mb-4 font-bn">🏠 সংসারের খরচ</h3>
                        <p class="text-gray-400 mb-6 font-bn">মাসের শুরুতে বাজেট ঠিক করে রাখুন — মাস শেষে আর চমকে যেতে হবে না।</p>
                        <ul class="space-y-3 text-sm font-bn">
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-teal-500/20 flex items-center justify-center text-teal-400 text-xs">✓</span> আয়-ব্যয়ের পূর্ণ হিসাব</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-teal-500/20 flex items-center justify-center text-teal-400 text-xs">✓</span> ক্যাটাগরি ধরে বাজেট</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-teal-500/20 flex items-center justify-center text-teal-400 text-xs">✓</span> সঞ্চয়ের লক্ষ্য ঠিক করা</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6">
                        <h4 class="text-sm font-semibold mb-4 text-teal-300 font-bn">নভেম্বরের বাজেট</h4>
                        <div class="space-y-4">
                            <div><div class="flex justify-between text-sm mb-2 font-bn"><span>🍕 খাবার</span><span class="font-medium">৳৮,৫০০ / ৳১২,০০০</span></div><div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-full" style="width:70%"></div></div></div>
                            <div><div class="flex justify-between text-sm mb-2 font-bn"><span>🚌 যাতায়াত</span><span class="font-medium">৳৩,২০০ / ৳৪,০০০</span></div><div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-teal-400 to-cyan-500 rounded-full" style="width:80%"></div></div></div>
                            <div><div class="flex justify-between text-sm mb-2 font-bn"><span>🏠 বাসা ভাড়া</span><span class="font-medium">৳৮,০০০ / ৳৮,০০০</span></div><div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-orange-400 to-red-500 rounded-full" style="width:100%"></div></div></div>
                        </div>
                    </div>
                </div>
                <div x-show="showcaseTab === 'business'" x-transition class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-3xl font-bold font-display mb-4 font-bn">🏪 দোকানের হিসাব</h3>
                        <p class="text-gray-400 mb-6 font-bn">কাস্টমারের বাকি থেকে শুরু করে প্রতিদিনের বিক্রয় — সব লিখে রাখুন। ইনভয়েস তৈরি করুন, PDF আকারে পাঠান।</p>
                        <ul class="space-y-3 text-sm font-bn">
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 text-xs">✓</span> সুন্দর ইনভয়েস তৈরি</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 text-xs">✓</span> কাস্টমার ও সাপ্লায়ারের খাতা</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 text-xs">✓</span> বাকি ও লাভ-ক্ষতির হিসাব</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-6">
                        <h4 class="text-sm font-semibold mb-4 text-orange-300 font-bn">সাম্প্রতিক ইনভয়েস</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div><p class="text-xs font-mono text-gray-400">INV-1024</p><p class="text-sm font-medium font-bn">করিম ভাই</p></div><div class="text-right"><p class="text-sm font-bold font-bn">৳২,৪৫০</p><span class="text-xs text-emerald-400 font-bn">পরিশোধিত</span></div></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div><p class="text-xs font-mono text-gray-400">INV-1023</p><p class="text-sm font-medium font-bn">রহিম স্টোর</p></div><div class="text-right"><p class="text-sm font-bold font-bn">৳৫,২০০</p><span class="text-xs text-amber-400 font-bn">বাকি</span></div></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div><p class="text-xs font-mono text-gray-400">INV-1022</p><p class="text-sm font-medium font-bn">সালাম ট্রেডার্স</p></div><div class="text-right"><p class="text-sm font-bold font-bn">৳১,৮০০</p><span class="text-xs text-emerald-400 font-bn">পরিশোধিত</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block glass text-emerald-300 text-xs font-semibold px-4 py-1.5 rounded-full mb-5 tracking-[0.2em] font-bn">ফিচার</span>
                <h2 class="text-3xl md:text-5xl font-display mb-4 font-bn">হিসাব রাখুন — <span class="gradient-green">মাথা না ঘামিয়েই</span></h2>
                <p class="text-gray-400 text-lg font-bn">যা যা দরকার — সব এক জায়গায়, সহজ ভাষায়</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="card-3d glass rounded-2xl p-8"><div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-emerald-500/30">🎓</div><h3 class="text-lg font-bold mb-2 font-bn">মেস ও মিল রেট</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">বাজার, ডিপোজিট আর মিল — সব যোগ-বিয়োগ নিজে থেকেই হয়ে যাবে।</p></div>
                <div class="card-3d glass rounded-2xl p-8"><div class="w-14 h-14 bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-teal-500/30">🏠</div><h3 class="text-lg font-bold mb-2 font-bn">সংসারের বাজেট</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">প্রতিদিনের খরচ থেকে মাসিক বাজেট — নিজের হাতের মুঠোয় রাখুন।</p></div>
                <div class="card-3d glass rounded-2xl p-8"><div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-orange-500/30">🧾</div><h3 class="text-lg font-bold mb-2 font-bn">দোকানের ইনভয়েস</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">এক ক্লিকে সুন্দর ইনভয়েস। কাস্টমার আর বাকির খাতা — সব পরিষ্কার।</p></div>
                <div class="card-3d glass rounded-2xl p-8"><div class="w-14 h-14 bg-gradient-to-br from-lime-400 to-lime-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-lime-500/30">📊</div><h3 class="text-lg font-bold mb-2 font-bn">চোখের সামনে রিপোর্ট</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">সুন্দর চার্টে পুরো মাসের ছবি। কোথায় খরচ বাড়ছে — সব স্পষ্ট।</p></div>
                <div class="card-3d glass rounded-2xl p-8"><div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-cyan-500/30">📱</div><h3 class="text-lg font-bold mb-2 font-bn">যেকোনো জায়গা থেকে</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">ফোনে, ল্যাপটপে, ট্যাবে — যেখানে খুশি ব্যবহার করুন।</p></div>
                <div class="card-3d glass rounded-2xl p-8"><div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-amber-500/30">🔒</div><h3 class="text-lg font-bold mb-2 font-bn">টাকার খবর শুধু আপনার</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">আপনার সব ডেটা এনক্রিপ্টেড। কেউ দেখতে পাবে না।</p></div>
            </div>
        </div>
    </section>

    <!-- COMPARE -->
    <section id="compare" class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block glass text-emerald-300 text-xs font-semibold px-4 py-1.5 rounded-full mb-5 tracking-[0.2em] font-bn">তুলনা</span>
                <h2 class="text-3xl md:text-5xl font-display mb-4 font-bn">কেন <span class="gradient-green">হিসাবখাতা?</span></h2>
            </div>
            <div class="glass-strong rounded-3xl overflow-hidden glow-green">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead><tr class="border-b border-white/10"><th class="text-left p-4 font-semibold font-bn">কী আছে</th><th class="p-4 text-center font-bn text-emerald-300">হিসাবখাতা</th><th class="p-4 text-center text-gray-400 text-xs font-bn">অন্য অ্যাপ</th><th class="p-4 text-center text-gray-400 text-xs font-bn">খাতা</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                            <tr><td class="p-4 text-sm font-bn">মেস মিল রেট</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-gray-500">✗</td><td class="p-4 text-center text-gray-500">✗</td></tr>
                            <tr><td class="p-4 text-sm font-bn">অটো মিল হিসাব</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-gray-500">✗</td><td class="p-4 text-center text-gray-500">✗</td></tr>
                            <tr><td class="p-4 text-sm font-bn">দোকানের ইনভয়েস</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-yellow-400 font-bn">কিছু</td><td class="p-4 text-center text-yellow-400 font-bn">কিছু</td></tr>
                            <tr><td class="p-4 text-sm font-bn">পুরোটাই বাংলায়</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-gray-500">✗</td><td class="p-4 text-center text-gray-500">✗</td></tr>
                            <tr><td class="p-4 text-sm font-semibold font-bn">মাসিক খরচ</td><td class="p-4 text-center text-emerald-400 font-bold font-bn">৳৪৯</td><td class="p-4 text-center text-gray-400 font-bn">৳১০০-৫০০</td><td class="p-4 text-center text-gray-400 font-bn">ফ্রি</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="py-24 relative z-10">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block glass text-emerald-300 text-xs font-semibold px-4 py-1.5 rounded-full mb-5 tracking-[0.2em] font-bn">দাম</span>
                <h2 class="text-3xl md:text-5xl font-display mb-4 font-bn">একদম <span class="gradient-green">সহজ হিসাব</span></h2>
            </div>
            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="glass rounded-2xl p-8 glow-green relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-emerald-400 to-emerald-600 text-black text-xs font-bold px-4 py-1 rounded-full font-bn">সবচেয়ে জনপ্রিয়</span>
                    <h3 class="text-xl font-bold font-display font-bn">মাসিক</h3>
                    <p class="text-5xl font-bold mt-4 font-display">৳৪৯<span class="text-lg font-normal text-gray-400 font-bn">/মাস</span></p>
                    <ul class="mt-6 space-y-3 text-sm font-bn">
                        <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> সব ফিচার খোলা</li>
                        <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> কোনো এন্ট্রি লিমিট নেই</li>
                        <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> রিপোর্ট ডাউনলোড</li>
                    </ul>
                    <a href="{{ route('register') }}" class="block text-center w-full mt-8 btn-green py-3 rounded-lg font-bold font-bn">ফ্রি শুরু করুন</a>
                </div>
                <div class="glass-strong rounded-2xl p-8 glow-green relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-400 to-orange-500 text-gray-900 text-xs font-bold px-4 py-1 rounded-full font-bn">২ মাস ফ্রি</span>
                    <h3 class="text-xl font-bold font-display font-bn">বাৎসরিক</h3>
                    <p class="text-5xl font-bold mt-4 font-display">৳৪৯৯<span class="text-lg font-normal text-gray-400 font-bn">/বছর</span></p>
                    <p class="text-sm text-emerald-400 mt-1 font-bn">মাসে পড়ে মাত্র ৳৪১</p>
                    <ul class="mt-6 space-y-3 text-sm font-bn">
                        <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> সব ফিচার খোলা</li>
                        <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> ২ মাস পুরোপুরি ফ্রি</li>
                        <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> ব্যাকআপ ডাউনলোড</li>
                    </ul>
                    <a href="{{ route('register') }}" class="block text-center w-full mt-8 glass-strong text-white py-3 rounded-lg font-bold font-bn">ফ্রি শুরু করুন</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-24 relative z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block glass text-emerald-300 text-xs font-semibold px-4 py-1.5 rounded-full mb-5 tracking-[0.2em] font-bn">প্রশ্নোত্তর</span>
                <h2 class="text-3xl md:text-5xl font-display mb-4 font-bn">যেটা <span class="gradient-green">জানতে চাইছেন</span></h2>
            </div>
            <div class="glass-strong rounded-3xl p-8 max-w-3xl mx-auto glow-green">
                <div x-data="{open:1}" class="space-y-3">
                    <div class="glass rounded-xl overflow-hidden"><button @click="open = open===1?null:1" class="w-full flex justify-between items-center p-5 text-left"><span class="font-semibold font-bn">ফ্রি ট্রায়াল কতদিন থাকবে?</span><span x-text="open===1?'−':'+'" class="text-2xl text-emerald-400"></span></button><div x-show="open===1" x-collapse class="px-5 pb-5 text-gray-400 text-sm font-bn">রেজিস্ট্রেশনের পর পুরো ৩০ দিন ফ্রি। সব ফিচার খোলা থাকবে।</div></div>
                    <div class="glass rounded-xl overflow-hidden"><button @click="open = open===2?null:2" class="w-full flex justify-between items-center p-5 text-left"><span class="font-semibold font-bn">ট্রায়াল শেষ হলে ডেটা হারাবে?</span><span x-text="open===2?'−':'+'" class="text-2xl text-emerald-400"></span></button><div x-show="open===2" x-collapse class="px-5 pb-5 text-gray-400 text-sm font-bn">না। আপনার সব তথ্য নিরাপদে থাকবে।</div></div>
                    <div class="glass rounded-xl overflow-hidden"><button @click="open = open===3?null:3" class="w-full flex justify-between items-center p-5 text-left"><span class="font-semibold font-bn">পরে ব্যবহারের ধরন বদলানো যাবে?</span><span x-text="open===3?'−':'+'" class="text-2xl text-emerald-400"></span></button><div x-show="open===3" x-collapse class="px-5 pb-5 text-gray-400 text-sm font-bn">হ্যাঁ, সেটিংস থেকে যেকোনো সময় মোড বদলাতে পারবেন।</div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- FINAL CTA -->
    <section class="py-24 relative z-10">
        <div class="max-w-4xl mx-auto px-6">
            <div class="glass-strong rounded-3xl p-12 md:p-16 text-center glow-green relative overflow-hidden">
                <div class="blob w-96 h-96 bg-emerald-600 top-0 left-0 opacity-25"></div>
                <div class="blob w-96 h-96 bg-orange-500 bottom-0 right-0 opacity-20"></div>
                <div class="relative">
                    <h2 class="text-4xl md:text-6xl font-display mb-4 font-bn">আজই <span class="gradient-shimmer">শুরু করুন</span></h2>
                    <p class="text-lg text-gray-300 mb-10 font-bn">প্রথম মাস পুরোটাই ফ্রি। কার্ড লাগবে না।</p>
                    <a href="{{ route('register') }}" class="inline-block btn-shine btn-green font-bold px-12 py-4 rounded-full text-lg font-bn">ফ্রি অ্যাকাউন্ট খুলুন →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="relative z-10 border-t border-emerald-500/10 py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-5 gap-10 mb-10">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <svg width="40" height="40" viewBox="0 0 200 200" fill="none"><defs><linearGradient id="footGreen" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#34D399"/><stop offset="100%" stop-color="#047857"/></linearGradient><linearGradient id="footOrange" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#EA580C"/></linearGradient></defs><path d="M 40 130 Q 100 160 160 130 L 160 60 Q 100 90 40 60 Z" fill="url(#footGreen)"/><rect x="65" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="119" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="65" y="80" width="70" height="16" rx="5" fill="#047857"/><circle cx="145" cy="115" r="32" fill="url(#footOrange)"/><text x="145" y="128" font-family="Inter" font-size="36" font-weight="800" fill="white" text-anchor="middle">৳</text></svg>
                        <div><span class="text-lg font-bold font-display font-bn">হিসাবখাতা</span><p class="text-[10px] tracking-[0.2em] text-emerald-400/70 -mt-0.5">SMART FINANCE</p></div>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4 font-bn">টাকার হিসাব — সহজ, পরিষ্কার, নিশ্চিন্ত। পুরোটাই বাংলায়।</p>
                </div>
                <div><h4 class="font-semibold mb-4 font-display text-emerald-300 font-bn">প্রোডাক্ট</h4><ul class="space-y-2 text-sm text-gray-500 font-bn"><li><a href="#features" class="hover:text-emerald-400">ফিচার</a></li><li><a href="#pricing" class="hover:text-emerald-400">দাম</a></li></ul></div>
                <div><h4 class="font-semibold mb-4 font-display text-emerald-300 font-bn">কোম্পানি</h4><ul class="space-y-2 text-sm text-gray-500 font-bn"><li><a href="#" class="hover:text-emerald-400">আমাদের কথা</a></li><li><a href="#" class="hover:text-emerald-400">যোগাযোগ</a></li></ul></div>
                <div><h4 class="font-semibold mb-4 font-display text-emerald-300 font-bn">আইনি</h4><ul class="space-y-2 text-sm text-gray-500 font-bn"><li><a href="#" class="hover:text-emerald-400">শর্তাবলী</a></li><li><a href="#" class="hover:text-emerald-400">প্রাইভেসি</a></li></ul></div>
            </div>
            <div class="border-t border-emerald-500/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500 font-bn">
                <p>© ২০২৫ হিসাবখাতা। সর্বস্বত্ব সংরক্ষিত।</p>
                <div class="flex gap-6 items-center"><span>🇧🇩 বাংলাদেশে তৈরি</span><span class="text-gray-700">|</span><span>ভাষা: বাংলা / English</span></div>
            </div>
        </div>
    </footer>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('heroChart');
    if (ctx) new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['১','৫','১০','১৫','২০','২৫','৩০'],
            datasets: [
                { label: 'আয়', data: [5000, 12000, 18000, 25000, 32000, 38000, 45000], borderColor: '#34D399', backgroundColor: 'rgba(52,211,153,0.15)', fill: true, tension: 0.4, pointBackgroundColor: '#34D399', pointBorderColor: '#06130D', pointRadius: 4, borderWidth: 2 },
                { label: 'ব্যয়', data: [3000, 7000, 11000, 16000, 20000, 25000, 28500], borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.4, pointBackgroundColor: '#F59E0B', pointBorderColor: '#06130D', pointRadius: 4, borderWidth: 2 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true, labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 10 } } },
            scales: { x: { grid: { color: 'rgba(16,185,129,0.05)' }, ticks: { color: '#94a3b8' } }, y: { grid: { color: 'rgba(16,185,129,0.05)' }, ticks: { color: '#94a3b8' } } }
        }
    });
</script>
@endpush
