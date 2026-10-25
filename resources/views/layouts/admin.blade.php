<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    dark: JSON.parse(localStorage.getItem('theme-dark') ?? 'true'),
    toggleDark() { this.dark = !this.dark; localStorage.setItem('theme-dark', JSON.stringify(this.dark)); },
    page: @js($adminPage ?? 'admin-dashboard'),
    subTab: 'transactions',
    sidebarOpen: false,
    modalOpen: false,
    modalTitle: '',
    staffModalOpen: @js(old('form') === 'staff'),
    toastMsg: window.__flashToast || '',
    showToast: Boolean(window.__flashToast),
    pageTitle() {
        const titles = {
            'admin-dashboard': @js(__('site.admin.dashboard_overview')),
            'system-users': @js(__('site.admin.system_users')),
            'system-activity': @js(__('site.admin.system_user_activity')),
            'developer-access': @js(__('site.admin.developer_access_control')),
            'roles-permission': @js(__('site.admin.roles_permission')),
            'client-users': @js(__('site.admin.client_users')),
            'client-settings': @js(__('site.admin.client_settings')),
            'admin-subscriptions': @js(__('site.admin.subscription_management')),
            'admin-pricing': @js(__('site.admin.pricing_plans')),
            'admin-content': @js(__('site.admin.page_content')),
            'admin-blog': @js(__('site.admin.blog_articles')),
            'admin-reviews': @js(__('site.admin.testimonials')),
            'admin-profile': @js(__('site.admin.admin_profile')),
        };
        return titles[this.page] ?? '';
    },
}" :class="dark ? 'dark' : ''">
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
        --brand-bg: #050B14;
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
        color: #050B14;
        box-shadow: 0 0 24px rgba(16, 185, 129, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.4);
        transition: all 0.2s;
    }
    .btn-emerald:hover { box-shadow: 0 0 34px rgba(16, 185, 129, 0.55); transform: translateY(-1px); }
    .nav-parent { transition: all 0.2s; }
    .nav-parent:hover { background: rgba(255, 255, 255, 0.03); }
    .nav-child { border-left: 2px solid transparent; transition: all 0.2s; }
    .nav-child.active { background: rgba(16, 185, 129, 0.1); color: #34D399; border-left-color: #34D399; font-weight: 600; }
    .nav-child:hover { background: rgba(255, 255, 255, 0.03); color: white; }
    .nav-active { background: rgba(16, 185, 129, 0.12); color: #34D399; }
    .nav-item { transition: all 0.2s; }
    .nav-item:hover { background: rgba(255, 255, 255, 0.03); color: white; }
    .admin-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .admin-scroll::-webkit-scrollbar-track { background: transparent; }
    .admin-scroll::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.3); border-radius: 10px; }
    [x-cloak] { display: none !important; }

    /* ---------- LIGHT MODE ---------- */
    html:not(.dark) {
        --brand-bg: #F1F5F9;
        --brand-surface: #FFFFFF;
        --brand-emerald: #059669;
        --brand-emerald-light: #10B981;
        --brand-emerald-dark: #047857;
    }
    html:not(.dark) body { color: #0F172A; }
    html:not(.dark) .glass { background: rgba(255, 255, 255, 0.75); border-color: rgba(16, 185, 129, 0.25); box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9); }
    html:not(.dark) .glass-strong { background: rgba(255, 255, 255, 0.92); border-color: rgba(16, 185, 129, 0.3); box-shadow: 0 12px 32px rgba(15, 23, 42, 0.1), inset 0 1px 0 rgba(255, 255, 255, 1); }
    html:not(.dark) .text-white { color: #0F172A; }
    html:not(.dark) .text-gray-300 { color: #475569; }
    html:not(.dark) .text-gray-400 { color: #64748B; }
    html:not(.dark) .text-gray-500 { color: #64748B; }
    html:not(.dark) .text-emerald-300,html:not(.dark) .text-emerald-400 { color: #047857; }
    html:not(.dark) .text-teal-400 { color: #0D9488; }
    html:not(.dark) .text-orange-400 { color: #EA580C; }
    html:not(.dark) .text-red-400 { color: #DC2626; }
    html:not(.dark) .bg-white\/5 { background-color: rgba(15, 23, 42, 0.05); }
    html:not(.dark) .bg-black\/40 { background-color: rgba(15, 23, 42, 0.06); }
    html:not(.dark) .border-white\/5,html:not(.dark) .border-white\/10 { border-color: rgba(15, 23, 42, 0.1); }
    html:not(.dark) input,html:not(.dark) select,html:not(.dark) textarea { background: #fff; color: #0F172A; border-color: rgba(15, 23, 42, 0.12); }
    html:not(.dark) input::placeholder,html:not(.dark) select,html:not(.dark) textarea::placeholder { color: #94A3B8; }
    html:not(.dark) .nav-parent:hover,html:not(.dark) .nav-item:hover,html:not(.dark) .nav-child:hover { background: rgba(15, 23, 42, 0.04); color: #0F172A; }
    html:not(.dark) .nav-child.active { background: rgba(16, 185, 129, 0.1); color: #047857; border-left-color: #10B981; }
    html:not(.dark) .admin-scroll::-webkit-scrollbar-thumb { background: rgba(15, 23, 42, 0.2); }
    html:not(.dark) .bg-red-500\/10 { background-color: rgba(220, 38, 38, 0.08); }
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
            <button @click="sidebarOpen = false" class="lg:hidden ml-auto text-gray-400 hover:text-white p-1" aria-label="{{ __('site.admin.close_menu') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto admin-scroll">
            <a href="#" @click.prevent="page = 'admin-dashboard'; sidebarOpen = false"
                :class="page === 'admin-dashboard' ? 'nav-active' : 'text-gray-400'"
                class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                {{ __('site.admin.dashboard') }}
            </a>

            <!-- 1. USER MANAGEMENT -->
            <div x-data="{ open: @js(in_array($adminPage ?? '', ['system-users', 'system-activity', 'developer-access', 'roles-permission'], true)) }">
                <button @click="open = !open" :class="open ? 'text-white' : 'text-gray-400'" class="nav-parent w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-bn text-sm">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        {{ __('site.admin.user_management') }}
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="ml-6 mt-1 space-y-1 border-l border-white/10 pl-2">
                    <a href="#" @click.prevent="page = 'system-users'; sidebarOpen = false" :class="page === 'system-users' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.system_users') }}</a>
                    <a href="#" @click.prevent="page = 'system-activity'; sidebarOpen = false" :class="page === 'system-activity' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.system_user_activity') }}</a>
                    <a href="#" @click.prevent="page = 'developer-access'; sidebarOpen = false" :class="page === 'developer-access' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.developer_access_control') }}</a>
                    <a href="#" @click.prevent="page = 'roles-permission'; sidebarOpen = false" :class="page === 'roles-permission' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.roles_permission') }}</a>
                </div>
            </div>

            <!-- 2. CLIENT MANAGEMENT -->
            <div x-data="{ open: @js(in_array($adminPage ?? '', ['client-users', 'client-settings', 'admin-subscriptions'], true)) }">
                <button @click="open = !open" :class="open ? 'text-white' : 'text-gray-400'" class="nav-parent w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-bn text-sm">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        {{ __('site.admin.client_management') }}
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="ml-6 mt-1 space-y-1 border-l border-white/10 pl-2">
                    <a href="#" @click.prevent="page = 'client-users'; sidebarOpen = false" :class="page === 'client-users' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.client_users') }}</a>
                    <a href="#" @click.prevent="page = 'client-settings'; sidebarOpen = false" :class="page === 'client-settings' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.client_settings') }}</a>
                    <a href="#" @click.prevent="page = 'admin-subscriptions'; sidebarOpen = false" :class="page === 'admin-subscriptions' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.subscriptions') }}</a>
                </div>
            </div>

            <!-- 3. CONTENT CMS -->
            <div x-data="{ open: @js(in_array($adminPage ?? '', ['admin-pricing', 'admin-content', 'admin-blog', 'admin-reviews'], true)) }">
                <button @click="open = !open" :class="open ? 'text-white' : 'text-gray-400'" class="nav-parent w-full flex items-center justify-between px-3 py-2.5 rounded-lg font-bn text-sm">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
                        {{ __('site.admin.content_cms') }}
                    </span>
                    <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 transition-transform text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" class="ml-6 mt-1 space-y-1 border-l border-white/10 pl-2">
                    <a href="#" @click.prevent="page = 'admin-pricing'; sidebarOpen = false" :class="page === 'admin-pricing' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.pricing_plans') }}</a>
                    <a href="#" @click.prevent="page = 'admin-content'; sidebarOpen = false" :class="page === 'admin-content' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.page_content') }}</a>
                    <a href="#" @click.prevent="page = 'admin-blog'; sidebarOpen = false" :class="page === 'admin-blog' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.blog_articles') }}</a>
                    <a href="#" @click.prevent="page = 'admin-reviews'; sidebarOpen = false" :class="page === 'admin-reviews' ? 'nav-child active' : 'nav-child text-gray-400'" class="block px-3 py-2 rounded-r-lg text-sm font-bn">{{ __('site.admin.testimonials') }}</a>
                </div>
            </div>

            <div class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-6 font-bn">{{ __('site.admin.account') }}</div>
            <a href="#" @click.prevent="page = 'admin-profile'; sidebarOpen = false"
                :class="page === 'admin-profile' ? 'nav-active' : 'text-gray-400'"
                class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('site.admin.profile') }}
            </a>
        </nav>

        <div class="p-4 border-t border-white/5 space-y-3">
            <button @click="page = 'admin-profile'; sidebarOpen = false" class="w-full flex items-center gap-3 rounded-lg p-1 -m-1 hover:bg-white/5 transition text-left" :title="@js(__('site.admin.profile'))">
                <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-[#050B14] font-bold shrink-0">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white font-bn truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                </div>
            </button>
        </div>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative min-w-0">

        <header class="admin-surface backdrop-blur-md border-b border-white/5 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3 z-10">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden glass w-9 h-9 rounded-lg flex items-center justify-center shrink-0" aria-label="{{ __('site.admin.open_menu') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-base sm:text-lg font-bold font-bn text-white truncate" x-text="pageTitle()">{{ __('site.admin.dashboard_overview') }}</h2>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <button @click="toggleDark()" class="glass w-9 h-9 rounded-lg flex items-center justify-center shrink-0 hover:scale-110 transition" aria-label="Toggle theme"><span x-show="!dark">🌙</span><span x-show="dark">☀️</span></button>
                @include('partials.language-switcher')
                <div x-show="page === 'system-users'" x-cloak class="hidden sm:flex items-center gap-2 bg-black/40 px-3 py-1.5 rounded-lg border border-white/10">
                    <span class="text-xs text-gray-400 font-bn">{{ __('site.admin.role_colon') }}</span>
                    <span class="text-xs text-emerald-400 font-bold font-bn">{{ __('site.admin.role_'.(auth()->user()->role ?: 'unknown')) }}</span>
                </div>
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
