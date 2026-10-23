<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    page: 'admin-dashboard',
    subTab: 'transactions',
    sidebarOpen: false,
    modalOpen: false,
    modalTitle: '',
    toastMsg: window.__flashToast || '',
    showToast: Boolean(window.__flashToast),
    pageTitle() {
                const titles = {
                    'admin-dashboard': @js(__('site.admin.dashboard_overview')),
                    'admin-users': @js(__('site.admin.user_management')),
                    'admin-subscriptions': @js(__('site.admin.subscription_management')),
                    'admin-pricing': @js(__('site.admin.pricing_plans')),
                    'admin-content': @js(__('site.admin.page_content')),
                    'admin-blog': @js(__('site.admin.blog_articles')),
                    'admin-reviews': @js(__('site.admin.testimonials')),
                };
        return titles[this.page] ?? '';
    },
}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<title>@yield('title', __('site.admin.title', ['brand' => __('site.common.brand')]))</title>
<meta name="description" content="Admin CMS — {{ __('site.common.brand') }}">
<script>window.__flashToast = @js(session('toast'));</script>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --brand-bg: #06130D;
        --brand-surface: #091C14;
        --brand-emerald: #10B981;
        --brand-emerald-light: #34D399;
        --brand-emerald-dark: #047857;
        --brand-gold: #F59E0B;
    }
    html { -webkit-text-size-adjust: 100%; }
    body { font-family: 'Inter', 'Hind Siliguri', sans-serif; background: var(--brand-bg); color: #f8fafc; }
    .font-bn { font-family: 'Hind Siliguri', sans-serif; }
    .font-display { font-family: 'Inter', sans-serif; font-weight: 800; letter-spacing: -0.03em; }
    .admin-bg { background: var(--brand-bg); }
    .admin-surface { background: var(--brand-surface); }
    .glass {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(16, 185, 129, 0.14);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }
    .glass-strong {
        background: rgba(16, 185, 129, 0.06);
        backdrop-filter: blur(28px) saturate(200%);
        -webkit-backdrop-filter: blur(28px) saturate(200%);
        border: 1px solid rgba(16, 185, 129, 0.22);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }
    .btn-emerald {
        background: linear-gradient(135deg, #34D399 0%, #10B981 50%, #047857 100%);
        color: #06130D;
        box-shadow: 0 0 24px rgba(16, 185, 129, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.4);
        transition: all 0.2s;
    }
    .btn-emerald:hover { box-shadow: 0 0 34px rgba(16, 185, 129, 0.55); transform: translateY(-1px); }
    .nav-active { background: rgba(16, 185, 129, 0.12); color: #34D399; border-right: 3px solid #34D399; }
    .nav-item { border-right: 3px solid transparent; transition: all 0.2s; }
    .nav-item:hover { background: rgba(255, 255, 255, 0.03); color: white; }
    .admin-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .admin-scroll::-webkit-scrollbar-track { background: transparent; }
    .admin-scroll::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.3); border-radius: 10px; }
    [x-cloak] { display: none !important; }
</style>
@stack('styles')
</head>
<body class="overflow-x-hidden">

<div class="flex h-screen overflow-hidden">

    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden" x-cloak></div>

    <!-- ================= SIDEBAR ================= -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 w-64 admin-bg border-r border-white/5 flex-shrink-0 flex flex-col transform -translate-x-full transition-transform duration-300 lg:static lg:translate-x-0">
        <div class="px-5 py-5 border-b border-white/5 flex items-center gap-3">
            <img src="{{ asset('favicon.svg') }}" alt="{{ __('site.common.brand') }}" class="w-9 h-9 rounded-lg">
            <div class="min-w-0">
                <h1 class="font-bold font-bn text-white truncate">{{ __('site.common.brand') }}</h1>
                <p class="text-[10px] tracking-widest text-emerald-400 font-bold">{{ __('site.admin.cms') }}</p>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden ml-auto text-gray-400 hover:text-white p-1" aria-label="Close menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto admin-scroll">
            <div class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-2 font-bn">{{ __('site.admin.core_system') }}</div>
            <a href="#" @click.prevent="page = 'admin-dashboard'; sidebarOpen = false" :class="page === 'admin-dashboard' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                {{ __('site.admin.dashboard') }}
            </a>
            <a href="#" @click.prevent="page = 'admin-users'; sidebarOpen = false" :class="page === 'admin-users' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                {{ __('site.admin.users') }}
            </a>
            <a href="#" @click.prevent="page = 'admin-subscriptions'; sidebarOpen = false" :class="page === 'admin-subscriptions' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                {{ __('site.admin.subscriptions') }}
            </a>

            <div class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6 font-bn">{{ __('site.admin.content_cms') }}</div>
            <a href="#" @click.prevent="page = 'admin-pricing'; sidebarOpen = false" :class="page === 'admin-pricing' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('site.admin.pricing_plans') }}
            </a>
            <a href="#" @click.prevent="page = 'admin-content'; sidebarOpen = false" :class="page === 'admin-content' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                {{ __('site.admin.page_content') }}
            </a>
            <a href="#" @click.prevent="page = 'admin-blog'; sidebarOpen = false" :class="page === 'admin-blog' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
                {{ __('site.admin.blog_articles') }}
            </a>
            <a href="#" @click.prevent="page = 'admin-reviews'; sidebarOpen = false" :class="page === 'admin-reviews' ? 'nav-active' : 'text-gray-400'" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                {{ __('site.admin.testimonials') }}
            </a>
        </nav>

        <div class="p-4 border-t border-white/5 space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-[#06130D] font-bold shrink-0">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white font-bn truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400 font-bn">{{ __('site.admin.language') }}</span>
                @include('partials.language-switcher')
            </div>
        </div>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative min-w-0">

        <header class="admin-surface backdrop-blur-md border-b border-white/5 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3 z-10">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden glass w-9 h-9 rounded-lg flex items-center justify-center shrink-0" aria-label="Open menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-base sm:text-lg font-bold font-bn text-white truncate" x-text="pageTitle()">ড্যাশবোর্ড ওভারভিউ</h2>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                @include('partials.language-switcher')
                <a href="{{ route('home') }}" class="hidden sm:inline text-sm text-emerald-400 hover:text-white font-bn transition">{{ __('site.admin.view_site') }} ↗</a>
                <a href="{{ route('home') }}" class="sm:hidden text-emerald-400 p-2" aria-label="{{ __('site.admin.view_site') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-500/10 text-red-400 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-bn hover:bg-red-500/20 transition">{{ __('site.admin.logout') }}</button>
                </form>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 admin-bg admin-scroll">
            @yield('content')
        </main>
    </div>
</div>

<div x-show="showToast" x-cloak x-transition x-init="showToast && setTimeout(() => showToast = false, 2500)" class="fixed top-16 right-4 sm:right-6 z-[100] glass-strong text-emerald-300 px-4 sm:px-5 py-3 rounded-xl shadow-2xl text-sm font-bn" x-text="toastMsg"></div>

@stack('scripts')
</body>
</html>
