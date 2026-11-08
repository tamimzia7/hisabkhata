@php
    $studentUser = auth()->user();
    $unreadNotificationCount = $studentUser?->unreadNotifications()->count() ?? 0;
    $navMess = $navMess ?? null;
    $navLink = fn (string $name): string => $navMess ? route($name, $navMess) : route('student.messes.index');
    $navActive = fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    dark: JSON.parse(localStorage.getItem('theme-dark') ?? 'true'),
    toggleDark() { this.dark = !this.dark; localStorage.setItem('theme-dark', JSON.stringify(this.dark)); },
    sidebarOpen: false,
    toastMsg: window.__flashToast || '',
    showToast: Boolean(window.__flashToast),
}" :class="dark ? 'dark' : ''">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<title>@yield('title', __('student.brand'))</title>
<script>window.__flashToast = @js(session('toast'));</script>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@100;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { --brand-bg: #050B14; --brand-surface: #091C14; }
    html { -webkit-text-size-adjust: 100%; }
    body { font-family: 'Inter', 'Hind Siliguri', sans-serif; background: var(--brand-bg); color: #f8fafc; }
    .font-bn { font-family: 'Hind Siliguri', sans-serif; }
    .font-display { font-family: 'Inter', sans-serif; font-weight: 800; letter-spacing: -0.03em; }
    .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(20px) saturate(180%); border: 1px solid rgba(16,185,129,0.15); box-shadow: 0 8px 32px rgba(6,19,13,0.6), inset 0 1px 0 rgba(255,255,255,0.08); }
    .glass-strong { background: rgba(16,185,129,0.08); backdrop-filter: blur(28px) saturate(200%); border: 1px solid rgba(16,185,129,0.25); box-shadow: 0 12px 40px rgba(6,19,13,0.7), inset 0 1px 0 rgba(255,255,255,0.12); }
    .btn-emerald { background: linear-gradient(135deg,#34D399 0%,#10B981 50%,#047857 100%); color: #06130D; box-shadow: 0 0 24px rgba(16,185,129,0.35), inset 0 1px 0 rgba(255,255,255,0.4); transition: all .2s; }
    .btn-emerald:hover { box-shadow: 0 0 34px rgba(16,185,129,0.55); transform: translateY(-1px); }
    .btn-ghost { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); transition: all .2s; }
    .btn-ghost:hover { background: rgba(255,255,255,0.1); }
    [x-cloak] { display: none !important; }
    .student-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .student-scroll::-webkit-scrollbar-thumb { background: rgba(16,185,129,0.3); border-radius: 999px; }
    .field { width: 100%; border-radius: 0.6rem; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); padding: 0.55rem 0.8rem; font-size: 0.875rem; color: #fff; }
    .field:focus { outline: none; border-color: rgba(16,185,129,0.6); box-shadow: 0 0 0 2px rgba(16,185,129,0.2); }
    .field::placeholder { color: #64748b; }
    .field option { background: #0b1a13; }
    .kpi-label { font-size: 0.7rem; color: #94a3b8; }
    .table-wrap { overflow-x: auto; }
    .data-table { width: 100%; min-width: 640px; font-size: 0.8rem; border-collapse: collapse; }
    .data-table th { text-align: left; color: #94a3b8; font-weight: 600; padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); white-space: nowrap; }
    .data-table td { padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .data-table tbody tr:hover { background: rgba(255,255,255,0.03); }
</style>
</head>
<body class="min-h-screen">
<div class="flex min-h-screen" x-data>
    <!-- Overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm md:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed md:sticky top-0 left-0 z-40 flex h-screen w-64 shrink-0 -translate-x-full flex-col border-r border-white/10 bg-[#050B14]/95 backdrop-blur-xl transition-transform duration-200 md:translate-x-0">
        <div class="flex items-center gap-2 px-4 py-4 border-b border-white/10">
            <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 font-bold text-black">৳</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold font-bn text-white">{{ __('student.brand') }}</span>
                    <span class="block text-[9px] tracking-[0.2em] text-emerald-400">{{ __('student.tagline') }}</span>
                </span>
            </a>
            <button @click="sidebarOpen = false" class="ml-auto text-gray-400 hover:text-white md:hidden" aria-label="Close">✕</button>
        </div>

        @if($navMess)
            <div class="px-3 pt-3">
                <a href="{{ route('student.messes.index') }}" class="flex items-center justify-between gap-2 rounded-lg border border-emerald-400/20 bg-emerald-500/10 px-3 py-2 transition hover:bg-emerald-500/15">
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-bold text-emerald-300 font-bn">{{ $navMess->name }}</span>
                        <span class="block text-[10px] text-gray-500 font-bn">{{ __('student.nav.active_mess') }}</span>
                    </span>
                    <span class="text-[10px] text-gray-400">⇄</span>
                </a>
            </div>
        @endif

        <nav class="student-scroll flex-1 space-y-4 overflow-y-auto px-3 py-4 text-sm font-bn">
            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600">{{ __('student.nav.main') }}</p>
                <div class="space-y-0.5">
                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.dashboard') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>📊</span><span>{{ __('student.nav.dashboard') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.members.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.members.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>👥</span><span>{{ __('student.nav.members') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.meals.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.meals.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🍽️</span><span>{{ __('student.nav.meals') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.bazar.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.bazar.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🛒</span><span>{{ __('student.nav.bazar') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.deposits.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.deposits.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>💰</span><span>{{ __('student.nav.deposits') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.expenses.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.expenses.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🧾</span><span>{{ __('student.nav.expenses') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.accounts.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.accounts.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🏦</span><span>{{ __('student.nav.accounts') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.transactions.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.transactions.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🔁</span><span>{{ __('student.nav.transactions') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.settlements.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.settlements.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>📅</span><span>{{ __('student.nav.settlements') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.reports.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.reports.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>📈</span><span>{{ __('student.nav.reports') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600">{{ __('student.nav.settings') }}</p>
                <div class="space-y-0.5">
                    <a href="{{ $navLink('student.mess.settings.edit') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.settings.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>⚙️</span><span>{{ __('student.nav.mess_settings') }}</span>
                    </a>
                    <a href="{{ $navLink('student.mess.permissions.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.mess.permissions.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🔐</span><span>{{ __('student.nav.member_permissions') }}</span>
                    </a>
                    <a href="{{ route('student.profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('student.profile.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>👤</span><span>{{ __('student.nav.profile') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600">{{ __('student.nav.support') }}</p>
                <div class="space-y-0.5">
                    <a href="{{ route('tickets.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('tickets.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🎫</span><span>{{ __('student.nav.tickets') }}</span>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 transition {{ request()->routeIs('notifications.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                        <span>🔔</span><span>{{ __('student.nav.notifications') }}</span>
                        @if($unreadNotificationCount > 0)
                            <span class="ml-auto rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-400">{{ $unreadNotificationCount > 99 ? '99+' : localize_digits($unreadNotificationCount) }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </nav>

        <div class="border-t border-white/10 p-3">
            <div class="glass rounded-xl p-3 mb-2">
                <p class="text-[10px] text-gray-500 font-bn">{{ __('student.nav.logged_in_as') }}</p>
                <p class="truncate text-sm font-bold font-bn">{{ $studentUser->name }}</p>
                <p class="text-[10px] uppercase tracking-wide text-emerald-400">{{ __('student.nav.student_product') }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full rounded-lg bg-red-500/10 py-2 text-xs text-red-300 transition hover:bg-red-500/20 font-bn">{{ __('student.nav.logout') }}</button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex flex-wrap items-center gap-2 border-b border-white/10 bg-[#050B14]/80 px-3 py-3 backdrop-blur-xl sm:px-5">
            <button @click="sidebarOpen = true" class="glass flex h-9 w-9 items-center justify-center rounded-lg md:hidden" aria-label="Menu">☰</button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-base font-bold font-display font-bn text-white sm:text-lg">@yield('heading', __('student.brand'))</h1>
                @hasSection('subheading')
                    <p class="truncate text-[11px] text-gray-400 font-bn">@yield('subheading')</p>
                @endif
            </div>
            <div class="flex items-center gap-1.5">
                @if($navMess)
                    <a href="{{ route('student.messes.index') }}" class="hidden rounded-lg border border-white/10 bg-white/5 px-2.5 py-2 text-[11px] text-gray-300 transition hover:bg-white/10 sm:block font-bn">⇄ {{ __('student.nav.switch_mess') }}</a>
                @endif
                <x-notifications.bell :count="$unreadNotificationCount" />
                <button @click="toggleDark()" class="glass flex h-9 w-9 items-center justify-center rounded-full transition hover:scale-105" aria-label="Toggle theme">
                    <span x-show="!dark">🌙</span><span x-show="dark" x-cloak>☀️</span>
                </button>
                @include('partials.language-switcher')
            </div>
        </header>

        <main class="flex-1 px-3 py-4 sm:px-5 sm:py-6">
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300 font-bn">
                    {{ $errors->first() }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <div x-show="showToast" x-cloak x-transition x-init="showToast && setTimeout(() => showToast = false, 3200)"
         class="fixed bottom-5 right-4 z-[100] rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-200 shadow-2xl backdrop-blur-xl font-bn"
         x-text="toastMsg"></div>
</div>
</body>
</html>
