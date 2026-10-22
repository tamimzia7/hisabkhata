@extends('layouts.app')

@section('content')
<div id="top" class="pb-20 md:pb-0">
    <!-- NAVBAR -->
    <nav class="sticky top-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <svg width="44" height="44" viewBox="0 0 200 200" fill="none" class="w-9 h-9 sm:w-11 sm:h-11 group-hover:scale-105 transition-transform duration-300 drop-shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    <defs>
                        <linearGradient id="logoGreen" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#34D399"/><stop offset="100%" stop-color="#047857"/></linearGradient>
                        <linearGradient id="logoOrange" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#EA580C"/></linearGradient>
                    </defs>
                    <path d="M 40 130 Q 100 160 160 130 L 160 60 Q 100 90 40 60 Z" fill="url(#logoGreen)"/>
                    <rect x="65" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="119" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="65" y="80" width="70" height="16" rx="5" fill="#047857"/>
                    <circle cx="145" cy="115" r="32" fill="url(#logoOrange)"/><text x="145" y="128" font-family="Inter, sans-serif" font-size="36" font-weight="800" fill="white" text-anchor="middle">৳</text>
                </svg>
                <div class="hidden min-[400px]:block">
                    <span class="text-lg sm:text-xl font-bold tracking-tight font-bn text-white">{{ __('site.common.brand') }}</span>
                    <p class="text-[10px] tracking-[0.2em] text-emerald-400 -mt-0.5">{{ __('site.common.smart_finance') }}</p>
                </div>
            </a>
            <div class="hidden lg:flex items-center gap-1">
                <a href="#showcase" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">{{ __('site.nav.showcase') }}</a>
                <a href="#features" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">{{ __('site.nav.features') }}</a>
                <a href="#compare" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">{{ __('site.nav.compare') }}</a>
                <a href="#pricing" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">{{ __('site.nav.pricing') }}</a>
                <a href="#faq" class="text-sm text-gray-300 hover:text-emerald-300 px-4 py-2 rounded-lg hover:bg-emerald-500/5 transition font-bn">{{ __('site.nav.faq') }}</a>
            </div>
            <div class="hidden lg:flex items-center gap-3">
                <button @click="dark = !dark" class="w-10 h-10 glass rounded-full flex items-center justify-center hover:scale-110 transition"><span x-show="!dark">🌙</span><span x-show="dark">☀️</span></button>
                @include('partials.language-switcher')
                @guest
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-300 hover:text-white px-4 py-2 font-bn">{{ __('site.nav.login') }}</a>
                    <a href="{{ route('register') }}" class="btn-shine btn-green text-sm font-bold px-6 py-2.5 rounded-full font-bn">{{ __('site.nav.start_free') }}</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-shine btn-green text-sm font-bold px-6 py-2.5 rounded-full font-bn">{{ __('site.nav.dashboard') }}</a>
                @endguest
            </div>
            <div class="flex lg:hidden items-center gap-1 sm:gap-1.5">
                @include('partials.language-switcher', ['compact' => true])
                @guest
                    <a href="{{ route('login') }}" aria-label="{{ __('site.nav.login') }}" title="{{ __('site.nav.login') }}" class="w-9 h-9 glass rounded-lg flex items-center justify-center text-gray-300 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    </a>
                    <a href="{{ route('register') }}" aria-label="{{ __('site.auth.register') }}" title="{{ __('site.auth.register') }}" class="w-9 h-9 btn-green rounded-lg flex items-center justify-center transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.25"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 19.5a5.25 5.25 0 0 1 10.5 0M18 7.5v6m3-3h-6"/></svg>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" aria-label="{{ __('site.nav.dashboard') }}" title="{{ __('site.nav.dashboard') }}" class="w-9 h-9 btn-green rounded-lg flex items-center justify-center transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
                    </a>
                @endguest
                <button @click="mobileMenu = !mobileMenu" aria-label="{{ __('site.nav.mobile_label') }}" class="glass w-9 h-9 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
        <div x-show="mobileMenu" x-transition class="lg:hidden px-4 pb-4 pt-2 space-y-1 border-t border-emerald-500/10 max-h-[calc(100vh-4.5rem)] overflow-y-auto">
            <a href="#top" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">{{ __('site.nav.home') }}</a>
            <a href="#showcase" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">{{ __('site.nav.showcase') }}</a>
            <a href="#features" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">{{ __('site.nav.features') }}</a>
            <a href="#compare" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">{{ __('site.nav.compare') }}</a>
            <a href="#pricing" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">{{ __('site.nav.pricing') }}</a>
            <a href="#faq" @click="mobileMenu=false" class="block py-2 text-gray-300 font-bn">{{ __('site.nav.faq') }}</a>
            <div class="py-2">
                @include('partials.language-switcher')
            </div>
            @guest
                <a href="{{ route('login') }}" @click="mobileMenu=false" class="block text-center glass py-2.5 rounded-lg font-bold mt-2 font-bn">{{ __('site.nav.login') }}</a>
                <a href="{{ route('register') }}" @click="mobileMenu=false" class="block text-center btn-green py-2.5 rounded-lg font-bold mt-2 font-bn">{{ __('site.nav.start_free_short') }}</a>
            @else
                <a href="{{ route('dashboard') }}" @click="mobileMenu=false" class="block text-center btn-green py-2.5 rounded-lg font-bold mt-2 font-bn">{{ __('site.nav.dashboard_short') }}</a>
            @endguest
        </div>
    </nav>

    <!-- HERO -->
    <section class="relative pt-12 pb-14 sm:pt-16 md:pt-28 md:pb-24 z-10">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid md:grid-cols-2 gap-10 md:gap-12 items-center">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 glass rounded-full px-3 sm:px-5 py-2 text-xs sm:text-sm font-medium mb-6 sm:mb-8 glow-green">
                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                        <span class="text-emerald-200 font-bn">{{ __('site.hero.badge') }}</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-6xl lg:text-7xl font-display leading-[1.05] mb-5 sm:mb-6 font-bn">
                        {{ __('site.hero.title_before') }} <span class="gradient-shimmer">{{ __('site.hero.title_highlight') }}</span><br><span class="text-white">{{ __('site.hero.title_after') }}</span><span class="cursor"></span>
                    </h1>
                    <p class="text-base sm:text-lg md:text-xl text-gray-300 mb-8 sm:mb-10 leading-relaxed max-w-lg font-bn">{{ __('site.hero.subtitle_before') }} <span class="text-emerald-300">{{ __('site.hero.subtitle_highlight') }}</span> {{ __('site.hero.subtitle_after') }}</p>
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-8 sm:mb-10">
                        <a href="{{ route('register') }}" class="btn-shine btn-green group px-6 sm:px-8 py-3.5 sm:py-4 font-bold rounded-full text-center flex items-center justify-center gap-2 font-bn">{{ __('site.hero.cta') }} <span class="group-hover:translate-x-1 transition">→</span></a>
                        <a href="#showcase" class="glass px-6 sm:px-8 py-3.5 sm:py-4 font-bold rounded-full hover:bg-white/10 transition text-center flex items-center justify-center gap-2 font-bn">
                            <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.8A1 1 0 004.8 3.6v12.8a1 1 0 001.5.8l10.4-6.4a1 1 0 000-1.6L6.3 2.8z"/></svg> {{ __('site.hero.secondary') }}
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-6 text-xs sm:text-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex -space-x-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 border-2 border-[#06130D]"></div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 border-2 border-[#06130D]"></div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-lime-400 to-lime-600 border-2 border-[#06130D]"></div>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 border-2 border-[#06130D] flex items-center justify-center text-white text-xs font-bold">+</div>
                            </div>
                            <span class="text-gray-300 font-bn"><strong class="text-white">{{ __('site.stats.users_value') }}</strong> {{ __('site.hero.users_suffix') }}</span>
                        </div>
                        <div class="h-4 w-px bg-white/10"></div>
                        <div class="flex items-center gap-1 text-amber-400">⭐⭐⭐⭐⭐ <span class="text-gray-300 ml-2 font-bn">{{ __('site.hero.rating') }}</span></div>
                    </div>
                </div>
                <div class="relative min-w-0">
                    <div class="relative min-w-0 glass-strong rounded-3xl overflow-hidden glow-green">
                        <div class="bg-white/5 px-5 py-3 flex items-center gap-2 border-b border-emerald-500/10">
                            <div class="w-3 h-3 bg-red-400/70 rounded-full"></div><div class="w-3 h-3 bg-yellow-400/70 rounded-full"></div><div class="w-3 h-3 bg-green-400/70 rounded-full"></div>
                            <div class="ml-2 sm:ml-4 min-w-0 flex-1 bg-white/5 rounded-lg px-2 sm:px-4 py-1.5 text-[10px] sm:text-xs text-gray-400 text-center truncate">hisabkhata.com/dashboard</div>
                        </div>
                        <div class="p-4 sm:p-6">
                            <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-4 sm:mb-5">
                                <div class="glass p-2.5 sm:p-3 rounded-xl"><p class="text-[10px] sm:text-xs text-gray-400 mb-1 font-bn">{{ __('site.hero.total_meals') }}</p><p class="text-lg sm:text-2xl font-bold font-display">{{ __('site.hero.mock_total_meals') }}</p></div>
                                <div class="glass p-2.5 sm:p-3 rounded-xl"><p class="text-[10px] sm:text-xs text-gray-400 mb-1 font-bn">{{ __('site.hero.meal_rate') }}</p><p class="text-lg sm:text-2xl font-bold font-display text-emerald-400">{{ __('site.hero.mock_meal_rate') }}</p></div>
                                <div class="glass p-2.5 sm:p-3 rounded-xl"><p class="text-[10px] sm:text-xs text-gray-400 mb-1 font-bn">{{ __('site.hero.balance') }}</p><p class="text-lg sm:text-2xl font-bold font-display text-orange-400">{{ __('site.hero.mock_balance') }}</p></div>
                            </div>
                            <div class="glass rounded-xl p-3 sm:p-4">
                                <div class="flex items-center justify-between gap-2 mb-3"><h4 class="text-xs sm:text-sm font-semibold font-bn">{{ __('site.hero.monthly_summary') }}</h4><span class="text-[10px] sm:text-xs text-emerald-400 font-bn">{{ __('site.hero.month') }}</span></div>
                                <canvas id="heroChart" height="70" class="w-full"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:flex absolute -bottom-6 -left-6 glass-strong rounded-2xl p-4 items-center gap-3 float glow-green">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-2xl">💰</div>
                        <div><p class="text-sm font-bold font-bn">{{ __('site.hero.price_per_month') }}</p><p class="text-xs text-gray-400 font-bn">{{ __('site.hero.all_features') }}</p></div>
                    </div>
                    <div class="hidden sm:flex absolute -top-4 -right-4 glass-strong rounded-2xl p-3 items-center gap-2 float glow-green">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center text-lg">🎓</div>
                        <div><p class="text-xs font-bold font-bn">{{ __('site.hero.mess_mode') }}</p><p class="text-xs text-gray-400 font-bn">{{ __('site.hero.auto_calc') }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="relative py-8 sm:py-10 z-10"><div class="max-w-7xl mx-auto px-4 sm:px-6"><div class="glass-strong rounded-2xl p-5 sm:p-6 md:p-8 glow-green">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-center">
            <div><p class="text-2xl sm:text-3xl md:text-4xl font-bold font-display text-emerald-400">{{ __('site.stats.users_value') }}</p><p class="text-xs sm:text-sm text-gray-400 mt-1 font-bn">{{ __('site.stats.users_label') }}</p></div>
            <div><p class="text-2xl sm:text-3xl md:text-4xl font-bold font-display text-teal-400">{{ __('site.stats.entries_value') }}</p><p class="text-xs sm:text-sm text-gray-400 mt-1 font-bn">{{ __('site.stats.entries_label') }}</p></div>
            <div><p class="text-2xl sm:text-3xl md:text-4xl font-bold font-display text-lime-400">{{ __('site.stats.uptime_value') }}</p><p class="text-xs sm:text-sm text-gray-400 mt-1 font-bn">{{ __('site.stats.uptime_label') }}</p></div>
            <div><p class="text-2xl sm:text-3xl md:text-4xl font-bold font-display text-orange-400">{{ __('site.stats.rating_value') }}</p><p class="text-xs sm:text-sm text-gray-400 mt-1 font-bn">{{ __('site.stats.rating_label') }}</p></div>
        </div>
    </div></div></section>

    <!-- MARQUEE -->
    <section class="py-6 border-y border-emerald-500/10 overflow-hidden relative z-10">
        <div class="flex marquee whitespace-nowrap gap-10 sm:gap-16 text-gray-500 font-medium text-sm sm:text-base font-bn">
            @foreach (range(1, 2) as $repeat)
                @foreach (['students', 'shops', 'families', 'rating', 'bangla', 'secure'] as $item)
                    <span>{{ __('site.marquee.'.$item) }}</span><span class="text-emerald-500/50">◆</span>
                @endforeach
            @endforeach
        </div>
    </section>

    <!-- SHOWCASE -->
    <section id="showcase" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24" x-data="{ showcaseTab: 'student' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.showcase.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.showcase.title_before') }} <span class="gradient-green">{{ __('site.showcase.title_highlight') }}</span></h2>
                <p class="text-base sm:text-lg text-gray-400 font-bn">{{ __('site.showcase.subtitle') }}</p>
            </div>
            <div class="flex justify-center gap-2 mb-8 sm:mb-10 flex-wrap">
                <button @click="showcaseTab = 'student'" :class="showcaseTab === 'student' ? 'tab-btn active' : 'glass'" class="px-4 sm:px-6 py-2.5 sm:py-3 rounded-full font-semibold text-xs sm:text-sm transition-all hover:scale-105 font-bn">{{ __('site.showcase.tab_student') }}</button>
                <button @click="showcaseTab = 'personal'" :class="showcaseTab === 'personal' ? 'tab-btn active' : 'glass'" class="px-4 sm:px-6 py-2.5 sm:py-3 rounded-full font-semibold text-xs sm:text-sm transition-all hover:scale-105 font-bn">{{ __('site.showcase.tab_personal') }}</button>
                <button @click="showcaseTab = 'business'" :class="showcaseTab === 'business' ? 'tab-btn active' : 'glass'" class="px-4 sm:px-6 py-2.5 sm:py-3 rounded-full font-semibold text-xs sm:text-sm transition-all hover:scale-105 font-bn">{{ __('site.showcase.tab_business') }}</button>
            </div>
            <div class="glass-strong rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-10 glow-green">
                <div x-show="showcaseTab === 'student'" x-transition class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold font-display mb-4 font-bn">{{ __('site.showcase.student_title') }}</h3>
                        <p class="text-sm sm:text-base text-gray-400 mb-6 font-bn">{{ __('site.showcase.student_desc') }}</p>
                        <ul class="space-y-3 text-sm font-bn">
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs">✓</span> {{ __('site.showcase.student_f1') }}</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs">✓</span> {{ __('site.showcase.student_f2') }}</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xs">✓</span> {{ __('site.showcase.student_f3') }}</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-4 sm:p-6">
                        <h4 class="text-sm font-semibold mb-4 text-emerald-300 font-bn">{{ __('site.showcase.member_balances') }}</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full"></div><span class="text-sm font-medium font-bn">{{ __('site.showcase.member_rafi') }}</span></div><span class="text-sm font-bold text-emerald-400 font-bn">{{ __('site.showcase.member_rafi_amount') }}</span></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-teal-400 to-teal-600 rounded-full"></div><span class="text-sm font-medium font-bn">{{ __('site.showcase.member_sakib') }}</span></div><span class="text-sm font-bold text-emerald-400 font-bn">{{ __('site.showcase.member_sakib_amount') }}</span></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full"></div><span class="text-sm font-medium font-bn">{{ __('site.showcase.member_tanvir') }}</span></div><span class="text-sm font-bold text-red-400 font-bn">{{ __('site.showcase.member_tanvir_amount') }}</span></div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-white/10 flex justify-between text-sm font-bn"><span class="text-gray-400">{{ __('site.showcase.meal_rate') }}</span><span class="font-bold text-emerald-400">{{ __('site.showcase.meal_rate_value') }}</span></div>
                    </div>
                </div>
                <div x-show="showcaseTab === 'personal'" x-transition class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold font-display mb-4 font-bn">{{ __('site.showcase.personal_title') }}</h3>
                        <p class="text-sm sm:text-base text-gray-400 mb-6 font-bn">{{ __('site.showcase.personal_desc') }}</p>
                        <ul class="space-y-3 text-sm font-bn">
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-teal-500/20 flex items-center justify-center text-teal-400 text-xs">✓</span> {{ __('site.showcase.personal_f1') }}</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-teal-500/20 flex items-center justify-center text-teal-400 text-xs">✓</span> {{ __('site.showcase.personal_f2') }}</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-teal-500/20 flex items-center justify-center text-teal-400 text-xs">✓</span> {{ __('site.showcase.personal_f3') }}</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-4 sm:p-6">
                        <h4 class="text-sm font-semibold mb-4 text-teal-300 font-bn">{{ __('site.showcase.budget_title') }}</h4>
                        <div class="space-y-4">
                            <div><div class="flex justify-between text-sm mb-2 font-bn"><span>{{ __('site.showcase.food') }}</span><span class="font-medium">{{ __('site.showcase.food_amount') }}</span></div><div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-full" style="width:70%"></div></div></div>
                            <div><div class="flex justify-between text-sm mb-2 font-bn"><span>{{ __('site.showcase.transport') }}</span><span class="font-medium">{{ __('site.showcase.transport_amount') }}</span></div><div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-teal-400 to-cyan-500 rounded-full" style="width:80%"></div></div></div>
                            <div><div class="flex justify-between text-sm mb-2 font-bn"><span>{{ __('site.showcase.rent') }}</span><span class="font-medium">{{ __('site.showcase.rent_amount') }}</span></div><div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-orange-400 to-red-500 rounded-full" style="width:100%"></div></div></div>
                        </div>
                    </div>
                </div>
                <div x-show="showcaseTab === 'business'" x-transition class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold font-display mb-4 font-bn">{{ __('site.showcase.business_title') }}</h3>
                        <p class="text-sm sm:text-base text-gray-400 mb-6 font-bn">{{ __('site.showcase.business_desc') }}</p>
                        <ul class="space-y-3 text-sm font-bn">
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 text-xs">✓</span> {{ __('site.showcase.business_f1') }}</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 text-xs">✓</span> {{ __('site.showcase.business_f2') }}</li>
                            <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-400 text-xs">✓</span> {{ __('site.showcase.business_f3') }}</li>
                        </ul>
                    </div>
                    <div class="glass rounded-2xl p-4 sm:p-6">
                        <h4 class="text-sm font-semibold mb-4 text-orange-300 font-bn">{{ __('site.showcase.recent_invoices') }}</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div><p class="text-xs font-mono text-gray-400">INV-1024</p><p class="text-sm font-medium font-bn">{{ __('site.showcase.customer_karim') }}</p></div><div class="text-right"><p class="text-sm font-bold font-bn">{{ __('site.showcase.invoice_karim_amount') }}</p><span class="text-xs text-emerald-400 font-bn">{{ __('site.showcase.paid') }}</span></div></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div><p class="text-xs font-mono text-gray-400">INV-1023</p><p class="text-sm font-medium font-bn">{{ __('site.showcase.customer_rahim') }}</p></div><div class="text-right"><p class="text-sm font-bold font-bn">{{ __('site.showcase.invoice_rahim_amount') }}</p><span class="text-xs text-amber-400 font-bn">{{ __('site.showcase.due') }}</span></div></div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg"><div><p class="text-xs font-mono text-gray-400">INV-1022</p><p class="text-sm font-medium font-bn">{{ __('site.showcase.customer_salam') }}</p></div><div class="text-right"><p class="text-sm font-bold font-bn">{{ __('site.showcase.invoice_salam_amount') }}</p><span class="text-xs text-emerald-400 font-bn">{{ __('site.showcase.paid') }}</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.features.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.features.title_before') }} <span class="gradient-green">{{ __('site.features.title_highlight') }}</span></h2>
                <p class="text-base sm:text-lg text-gray-400 font-bn">{{ __('site.features.subtitle') }}</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div class="card-3d glass rounded-2xl p-6 sm:p-8"><div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-emerald-500/30">🎓</div><h3 class="text-lg font-bold mb-2 font-bn">{{ __('site.features.mess_title') }}</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.features.mess_desc') }}</p></div>
                <div class="card-3d glass rounded-2xl p-6 sm:p-8"><div class="w-14 h-14 bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-teal-500/30">🏠</div><h3 class="text-lg font-bold mb-2 font-bn">{{ __('site.features.budget_title') }}</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.features.budget_desc') }}</p></div>
                <div class="card-3d glass rounded-2xl p-6 sm:p-8"><div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-orange-500/30">🧾</div><h3 class="text-lg font-bold mb-2 font-bn">{{ __('site.features.invoice_title') }}</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.features.invoice_desc') }}</p></div>
                <div class="card-3d glass rounded-2xl p-6 sm:p-8"><div class="w-14 h-14 bg-gradient-to-br from-lime-400 to-lime-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-lime-500/30">📊</div><h3 class="text-lg font-bold mb-2 font-bn">{{ __('site.features.reports_title') }}</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.features.reports_desc') }}</p></div>
                <div class="card-3d glass rounded-2xl p-6 sm:p-8"><div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-cyan-500/30">📱</div><h3 class="text-lg font-bold mb-2 font-bn">{{ __('site.features.anywhere_title') }}</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.features.anywhere_desc') }}</p></div>
                <div class="card-3d glass rounded-2xl p-6 sm:p-8"><div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center text-2xl mb-5 shadow-lg shadow-amber-500/30">🔒</div><h3 class="text-lg font-bold mb-2 font-bn">{{ __('site.features.privacy_title') }}</h3><p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.features.privacy_desc') }}</p></div>
            </div>
        </div>
    </section>

    <!-- COMPARE -->
    <section id="compare" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.compare.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.compare.title_before') }} <span class="gradient-green">{{ __('site.compare.title_highlight') }}</span></h2>
            </div>
            <div class="glass-strong rounded-3xl overflow-hidden glow-green">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px]">
                        <thead><tr class="border-b border-white/10"><th class="text-left p-4 font-semibold font-bn">{{ __('site.compare.feature') }}</th><th class="p-4 text-center font-bn text-emerald-300">{{ __('site.compare.hisabkhata') }}</th><th class="p-4 text-center text-gray-400 text-xs font-bn">{{ __('site.compare.other_apps') }}</th><th class="p-4 text-center text-gray-400 text-xs font-bn">{{ __('site.compare.notebook') }}</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                            <tr><td class="p-4 text-sm font-bn">{{ __('site.compare.mess_meal_rate') }}</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-gray-500">✗</td><td class="p-4 text-center text-gray-500">✗</td></tr>
                            <tr><td class="p-4 text-sm font-bn">{{ __('site.compare.auto_meal') }}</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-gray-500">✗</td><td class="p-4 text-center text-gray-500">✗</td></tr>
                            <tr><td class="p-4 text-sm font-bn">{{ __('site.compare.shop_invoice') }}</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-yellow-400 font-bn">{{ __('site.compare.some') }}</td><td class="p-4 text-center text-yellow-400 font-bn">{{ __('site.compare.some') }}</td></tr>
                            <tr><td class="p-4 text-sm font-bn">{{ __('site.compare.fully_bangla') }}</td><td class="p-4 text-center text-emerald-400">✓</td><td class="p-4 text-center text-gray-500">✗</td><td class="p-4 text-center text-gray-500">✗</td></tr>
                            <tr><td class="p-4 text-sm font-semibold font-bn">{{ __('site.compare.monthly_cost') }}</td><td class="p-4 text-center text-emerald-400 font-bold font-bn">{{ __('site.compare.price') }}</td><td class="p-4 text-center text-gray-400 font-bn">{{ __('site.compare.other_price') }}</td><td class="p-4 text-center text-gray-400 font-bn">{{ __('site.compare.free') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.pricing.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.pricing.title_before') }} <span class="gradient-green">{{ __('site.pricing.title_highlight') }}</span></h2>
            </div>
            <div class="grid sm:grid-cols-2 gap-6 sm:gap-8 max-w-3xl mx-auto">
                <div class="glass rounded-2xl p-6 sm:p-8 glow-green relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-emerald-400 to-emerald-600 text-black text-xs font-bold px-4 py-1 rounded-full font-bn">{{ __('site.pricing.popular') }}</span>
                    <h3 class="text-xl font-bold font-display font-bn">{{ __('site.pricing.monthly') }}</h3>
                    <p class="text-4xl sm:text-5xl font-bold mt-4 font-display">{{ __('site.pricing.price_monthly') }}<span class="text-base sm:text-lg font-normal text-gray-400 font-bn">{{ __('site.pricing.per_month') }}</span></p>
                    <ul class="mt-6 space-y-3 text-sm font-bn">
                        @foreach (__('site.pricing.monthly_features') as $feature)
                            <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> {{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="block text-center w-full mt-8 btn-green py-3 rounded-lg font-bold font-bn">{{ __('site.pricing.start_free') }}</a>
                </div>
                <div class="glass-strong rounded-2xl p-6 sm:p-8 glow-green relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-400 to-orange-500 text-gray-900 text-xs font-bold px-4 py-1 rounded-full font-bn">{{ __('site.pricing.two_months_free') }}</span>
                    <h3 class="text-xl font-bold font-display font-bn">{{ __('site.pricing.yearly') }}</h3>
                    <p class="text-4xl sm:text-5xl font-bold mt-4 font-display">{{ __('site.pricing.price_yearly') }}<span class="text-base sm:text-lg font-normal text-gray-400 font-bn">{{ __('site.pricing.per_year') }}</span></p>
                    <p class="text-sm text-emerald-400 mt-1 font-bn">{{ __('site.pricing.only_monthly') }}</p>
                    <ul class="mt-6 space-y-3 text-sm font-bn">
                        @foreach (__('site.pricing.yearly_features') as $feature)
                            <li class="flex gap-2 items-center"><span class="text-emerald-400">✦</span> {{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="block text-center w-full mt-8 glass-strong text-white py-3 rounded-lg font-bold font-bn">{{ __('site.pricing.start_free') }}</a>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="how" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.how.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.how.title_before') }} <span class="gradient-green">{{ __('site.how.title_highlight') }}</span> {{ __('site.how.title_after') }}</h2>
                <p class="text-base sm:text-lg text-gray-400 font-bn">{{ __('site.how.subtitle') }}</p>
            </div>
            <div class="grid gap-4 sm:gap-8 md:grid-cols-3">
                <div class="glass-strong rounded-2xl p-6 sm:p-8 text-center glow-green">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-5 shadow-lg shadow-emerald-500/30">{{ __('site.how.step1_number') }}</div>
                    <h3 class="text-xl font-bold mb-2 font-bn">{{ __('site.how.step1_title') }}</h3>
                    <p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.how.step1_desc') }}</p>
                </div>
                <div class="glass-strong rounded-2xl p-6 sm:p-8 text-center glow-green">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-teal-400 to-teal-600 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-5 shadow-lg shadow-teal-500/30">{{ __('site.how.step2_number') }}</div>
                    <h3 class="text-xl font-bold mb-2 font-bn">{{ __('site.how.step2_title') }}</h3>
                    <p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.how.step2_desc') }}</p>
                </div>
                <div class="glass-strong rounded-2xl p-6 sm:p-8 text-center glow-green">
                    <div class="w-16 h-16 mx-auto bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-5 shadow-lg shadow-orange-500/30">{{ __('site.how.step3_number') }}</div>
                    <h3 class="text-xl font-bold mb-2 font-bn">{{ __('site.how.step3_title') }}</h3>
                    <p class="text-sm text-gray-400 leading-relaxed font-bn">{{ __('site.how.step3_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section id="testimonials" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.testimonials.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.testimonials.title_before') }} <span class="gradient-green">{{ __('site.testimonials.title_highlight') }}</span></h2>
                <p class="text-base sm:text-lg text-gray-400 font-bn">{{ __('site.testimonials.subtitle') }}</p>
            </div>
            <div class="grid gap-4 sm:gap-6 md:grid-cols-3">
                <div class="glass rounded-2xl p-6 sm:p-8">
                    <div class="text-amber-400 mb-3">★★★★★</div>
                    <p class="text-sm text-gray-300 leading-relaxed mb-5 font-bn">{{ __('site.testimonials.t1_quote') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full"></div>
                        <div><p class="text-sm font-semibold font-bn">{{ __('site.testimonials.t1_name') }}</p><p class="text-xs text-gray-500 font-bn">{{ __('site.testimonials.t1_role') }}</p></div>
                    </div>
                </div>
                <div class="glass rounded-2xl p-6 sm:p-8">
                    <div class="text-amber-400 mb-3">★★★★★</div>
                    <p class="text-sm text-gray-300 leading-relaxed mb-5 font-bn">{{ __('site.testimonials.t2_quote') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-teal-400 to-teal-600 rounded-full"></div>
                        <div><p class="text-sm font-semibold font-bn">{{ __('site.testimonials.t2_name') }}</p><p class="text-xs text-gray-500 font-bn">{{ __('site.testimonials.t2_role') }}</p></div>
                    </div>
                </div>
                <div class="glass rounded-2xl p-6 sm:p-8">
                    <div class="text-amber-400 mb-3">★★★★★</div>
                    <p class="text-sm text-gray-300 leading-relaxed mb-5 font-bn">{{ __('site.testimonials.t3_quote') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full"></div>
                        <div><p class="text-sm font-semibold font-bn">{{ __('site.testimonials.t3_name') }}</p><p class="text-xs text-gray-500 font-bn">{{ __('site.testimonials.t3_role') }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECURITY -->
    <section id="security" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="glass-strong rounded-2xl sm:rounded-3xl p-6 sm:p-10 md:p-16 glow-green grid md:grid-cols-2 gap-8 md:gap-10 items-center">
                <div>
                    <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.security.badge') }}</span>
                    <h2 class="text-3xl md:text-4xl font-display mb-4 font-bn">{{ __('site.security.title_before') }} <span class="gradient-green">{{ __('site.security.title_highlight') }}</span></h2>
                    <p class="text-sm sm:text-base text-gray-400 mb-6 font-bn">{{ __('site.security.desc') }}</p>
                    <ul class="space-y-3 text-sm font-bn">
                        <li class="flex items-center gap-3"><span class="text-emerald-400">✓</span> {{ __('site.security.b1') }}</li>
                        <li class="flex items-center gap-3"><span class="text-emerald-400">✓</span> {{ __('site.security.b2') }}</li>
                        <li class="flex items-center gap-3"><span class="text-emerald-400">✓</span> {{ __('site.security.b3') }}</li>
                        <li class="flex items-center gap-3"><span class="text-emerald-400">✓</span> {{ __('site.security.b4') }}</li>
                    </ul>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <div class="glass rounded-2xl p-4 sm:p-6 text-center"><div class="text-2xl sm:text-3xl mb-2">🔐</div><p class="text-xs sm:text-sm font-bn">{{ __('site.security.c1') }}</p></div>
                    <div class="glass rounded-2xl p-4 sm:p-6 text-center"><div class="text-2xl sm:text-3xl mb-2">☁️</div><p class="text-xs sm:text-sm font-bn">{{ __('site.security.c2') }}</p></div>
                    <div class="glass rounded-2xl p-4 sm:p-6 text-center"><div class="text-2xl sm:text-3xl mb-2">🛡️</div><p class="text-xs sm:text-sm font-bn">{{ __('site.security.c3') }}</p></div>
                    <div class="glass rounded-2xl p-4 sm:p-6 text-center"><div class="text-2xl sm:text-3xl mb-2">📱</div><p class="text-xs sm:text-sm font-bn">{{ __('site.security.c4') }}</p></div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-16 sm:py-20 md:py-24 relative z-10 scroll-mt-20 sm:scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <span class="inline-block glass text-emerald-300 text-[10px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 rounded-full mb-5 tracking-[0.15em] sm:tracking-[0.2em] font-bn">{{ __('site.faq.badge') }}</span>
                <h2 class="text-2xl sm:text-3xl md:text-5xl font-display mb-4 font-bn">{{ __('site.faq.title_before') }} <span class="gradient-green">{{ __('site.faq.title_highlight') }}</span></h2>
            </div>
            <div class="glass-strong rounded-2xl sm:rounded-3xl p-3 sm:p-8 max-w-3xl mx-auto glow-green">
                <div x-data="{open:1}" class="space-y-3">
                    <div class="glass rounded-xl overflow-hidden"><button @click="open = open===1?null:1" class="w-full flex justify-between items-center gap-3 p-4 sm:p-5 text-left"><span class="text-sm sm:text-base font-semibold font-bn">{{ __('site.faq.q1') }}</span><span x-text="open===1?'−':'+'" class="text-2xl text-emerald-400 shrink-0"></span></button><div x-show="open===1" x-collapse class="px-4 sm:px-5 pb-4 sm:pb-5 text-gray-400 text-xs sm:text-sm font-bn">{{ __('site.faq.a1') }}</div></div>
                    <div class="glass rounded-xl overflow-hidden"><button @click="open = open===2?null:2" class="w-full flex justify-between items-center gap-3 p-4 sm:p-5 text-left"><span class="text-sm sm:text-base font-semibold font-bn">{{ __('site.faq.q2') }}</span><span x-text="open===2?'−':'+'" class="text-2xl text-emerald-400 shrink-0"></span></button><div x-show="open===2" x-collapse class="px-4 sm:px-5 pb-4 sm:pb-5 text-gray-400 text-xs sm:text-sm font-bn">{{ __('site.faq.a2') }}</div></div>
                    <div class="glass rounded-xl overflow-hidden"><button @click="open = open===3?null:3" class="w-full flex justify-between items-center gap-3 p-4 sm:p-5 text-left"><span class="text-sm sm:text-base font-semibold font-bn">{{ __('site.faq.q3') }}</span><span x-text="open===3?'−':'+'" class="text-2xl text-emerald-400 shrink-0"></span></button><div x-show="open===3" x-collapse class="px-4 sm:px-5 pb-4 sm:pb-5 text-gray-400 text-xs sm:text-sm font-bn">{{ __('site.faq.a3') }}</div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- FINAL CTA -->
    <section class="py-16 sm:py-20 md:py-24 relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="glass-strong rounded-2xl sm:rounded-3xl p-6 sm:p-10 md:p-16 text-center glow-green relative overflow-hidden">
                <div class="blob w-96 h-96 bg-emerald-600 top-0 left-0 opacity-25"></div>
                <div class="blob w-96 h-96 bg-orange-500 bottom-0 right-0 opacity-20"></div>
                <div class="relative">
                    <h2 class="text-2xl sm:text-4xl md:text-6xl font-display mb-4 font-bn">{{ __('site.cta.title_before') }} <span class="gradient-shimmer">{{ __('site.cta.title_highlight') }}</span></h2>
                    <p class="text-base sm:text-lg text-gray-300 mb-8 sm:mb-10 font-bn">{{ __('site.cta.subtitle') }}</p>
                    <a href="{{ route('register') }}" class="inline-block btn-shine btn-green font-bold px-8 sm:px-12 py-3.5 sm:py-4 rounded-full text-base sm:text-lg font-bn">{{ __('site.cta.button') }}</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="relative z-10 border-t border-emerald-500/10 py-10 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 sm:gap-10 mb-8 sm:mb-10">
                <div class="col-span-2 md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <svg width="40" height="40" viewBox="0 0 200 200" fill="none"><defs><linearGradient id="footGreen" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#34D399"/><stop offset="100%" stop-color="#047857"/></linearGradient><linearGradient id="footOrange" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FCD34D"/><stop offset="100%" stop-color="#EA580C"/></linearGradient></defs><path d="M 40 130 Q 100 160 160 130 L 160 60 Q 100 90 40 60 Z" fill="url(#footGreen)"/><rect x="65" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="119" y="50" width="16" height="75" rx="5" fill="#047857"/><rect x="65" y="80" width="70" height="16" rx="5" fill="#047857"/><circle cx="145" cy="115" r="32" fill="url(#footOrange)"/><text x="145" y="128" font-family="Inter" font-size="36" font-weight="800" fill="white" text-anchor="middle">৳</text></svg>
                        <div><span class="text-lg font-bold font-display font-bn">{{ __('site.common.brand') }}</span><p class="text-[10px] tracking-[0.2em] text-emerald-400/70 -mt-0.5">{{ __('site.common.smart_finance') }}</p></div>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4 font-bn">{{ __('site.footer.desc') }}</p>
                </div>
                <div><h4 class="font-semibold mb-4 font-display text-emerald-300 font-bn">{{ __('site.footer.product') }}</h4><ul class="space-y-2 text-sm text-gray-500 font-bn"><li><a href="#features" class="hover:text-emerald-400">{{ __('site.footer.features') }}</a></li><li><a href="#pricing" class="hover:text-emerald-400">{{ __('site.footer.pricing') }}</a></li></ul></div>
                <div><h4 class="font-semibold mb-4 font-display text-emerald-300 font-bn">{{ __('site.footer.company') }}</h4><ul class="space-y-2 text-sm text-gray-500 font-bn"><li><a href="#" class="hover:text-emerald-400">{{ __('site.footer.about') }}</a></li><li><a href="#" class="hover:text-emerald-400">{{ __('site.footer.contact') }}</a></li></ul></div>
                <div><h4 class="font-semibold mb-4 font-display text-emerald-300 font-bn">{{ __('site.footer.legal') }}</h4><ul class="space-y-2 text-sm text-gray-500 font-bn"><li><a href="#" class="hover:text-emerald-400">{{ __('site.footer.terms') }}</a></li><li><a href="#" class="hover:text-emerald-400">{{ __('site.footer.privacy') }}</a></li></ul></div>
            </div>
            <div class="border-t border-emerald-500/10 pt-6 sm:pt-8 flex flex-col md:flex-row justify-between items-center gap-3 sm:gap-4 text-xs sm:text-sm text-gray-500 font-bn text-center md:text-left">
                <p>{{ __('site.footer.copyright') }}</p>
                <div class="flex flex-wrap gap-3 sm:gap-6 items-center justify-center"><span>{{ __('site.footer.made_in') }}</span><span class="text-gray-700">|</span><span>{{ __('site.footer.language') }}</span></div>
            </div>
        </div>
    </footer>

    @include('partials.mobile-bottom-nav')
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('heroChart');
    if (ctx) new Chart(ctx, {
        type: 'line',
        data: {
            labels: @js(__('site.hero.chart_labels')),
            datasets: [
                { label: @js(__('site.hero.chart_income')), data: [5000, 12000, 18000, 25000, 32000, 38000, 45000], borderColor: '#34D399', backgroundColor: 'rgba(52,211,153,0.15)', fill: true, tension: 0.4, pointBackgroundColor: '#34D399', pointBorderColor: '#06130D', pointRadius: 4, borderWidth: 2 },
                { label: @js(__('site.hero.chart_expense')), data: [3000, 7000, 11000, 16000, 20000, 25000, 28500], borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.4, pointBackgroundColor: '#F59E0B', pointBorderColor: '#06130D', pointRadius: 4, borderWidth: 2 }
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
