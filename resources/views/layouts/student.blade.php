@php
    $studentUser = auth()->user();
    $unreadNotificationCount = $studentUser?->unreadNotifications()->count() ?? 0;
    $unreadTicketCount = $studentUser ? \App\Models\Ticket::unreadTicketCountFor($studentUser) : 0;
    $navMess = $navMess ?? null;
    $navMember = $navMess && $studentUser ? $navMess->memberFor($studentUser) : null;
    $isMessOwner = $navMess && $studentUser && $navMess->owner_id === $studentUser->id;
    $can = fn (string $ability): bool => (bool) ($navMember?->allows($ability));
    $navLink = fn (string $name): string => $navMess ? route($name, $navMess) : route('student.messes.index');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    dark: JSON.parse(localStorage.getItem('theme-dark') ?? 'true'),
    toggleDark() { this.dark = !this.dark; localStorage.setItem('theme-dark', JSON.stringify(this.dark)); },
    sidebarOpen: false,
    collapsed: JSON.parse(localStorage.getItem('student-sidebar-collapsed') ?? 'false'),
    toggleCollapsed() { this.collapsed = !this.collapsed; localStorage.setItem('student-sidebar-collapsed', JSON.stringify(this.collapsed)); },
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
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
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
    .card-hover { transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .card-hover:hover { transform: translateY(-2px); border-color: rgba(52,211,153,0.4); box-shadow: 0 18px 45px -18px rgba(16,185,129,0.45); }
</style>
</head>
<body class="min-h-screen">
<div class="flex min-h-screen">
    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm md:hidden"></div>

    <!-- Sidebar -->
    <aside x-cloak :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0', collapsed ? 'md:w-20' : 'md:w-64']"
           class="fixed md:sticky top-0 left-0 z-40 flex h-screen w-72 shrink-0 -translate-x-full flex-col border-r border-white/10 bg-[#050B14]/95 backdrop-blur-xl transition-all duration-200 md:translate-x-0">
        <div class="flex items-center gap-2 border-b border-white/10 px-3 py-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 font-bold text-black">৳</span>
                <span class="min-w-0" :class="collapsed ? 'md:hidden' : ''">
                    <span class="block truncate text-sm font-bold font-bn text-white">{{ __('student.brand') }}</span>
                    <span class="block text-[9px] tracking-[0.2em] text-emerald-400">{{ __('student.tagline') }}</span>
                </span>
            </a>
            <button @click="sidebarOpen = false" class="ml-auto text-gray-400 hover:text-white md:hidden" aria-label="{{ __('student.nav.close') }}">✕</button>
        </div>

        @if($navMess)
            <div class="px-2 pt-3">
                <a href="{{ route('student.messes.index') }}" title="{{ $navMess->name }}"
                   class="flex items-center justify-between gap-2 rounded-lg border border-emerald-400/20 bg-emerald-500/10 px-2.5 py-2 transition hover:bg-emerald-500/15"
                   :class="collapsed ? 'md:justify-center' : ''">
                    <span class="min-w-0" :class="collapsed ? 'md:hidden' : ''">
                        <span class="block truncate text-xs font-bold text-emerald-300 font-bn">{{ $navMess->name }}</span>
                        <span class="block truncate text-[10px] text-gray-500 font-bn">{{ $navMess->address ?: __('student.nav.active_mess') }}</span>
                    </span>
                    <i data-lucide="chevrons-up-down" class="h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                </a>
            </div>
        @endif

        <nav class="student-scroll flex-1 space-y-4 overflow-y-auto px-2 py-4 text-sm font-bn">
            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600" :class="collapsed ? 'md:hidden' : ''">{{ __('student.nav.main') }}</p>
                <div class="space-y-0.5">
                    <x-student.nav-link :href="route('student.dashboard')" icon="layout-dashboard" :label="__('student.nav.dashboard')" :active="request()->routeIs('student.dashboard')" />
                    <x-student.nav-link :href="$navLink('student.mess.members.index')" icon="users" :label="__('student.nav.members')" :active="request()->routeIs('student.mess.members.*')" />
                    <x-student.nav-link :href="$navLink('student.mess.meals.index')" icon="utensils-crossed" :label="__('student.nav.meals')" :active="request()->routeIs('student.mess.meals.*')" />
                    <x-student.nav-link :href="$navLink('student.mess.bazar.index')" icon="shopping-cart" :label="__('student.nav.bazar')" :active="request()->routeIs('student.mess.bazar.*')" />
                    <x-student.nav-link :href="$navLink('student.mess.deposits.index')" icon="wallet" :label="__('student.nav.deposits')" :active="request()->routeIs('student.mess.deposits.*')" />
                    <x-student.nav-link :href="$navLink('student.mess.expenses.index')" icon="receipt" :label="__('student.nav.expenses')" :active="request()->routeIs('student.mess.expenses.*')" />
                    @if($can('manage_finance'))
                        <x-student.nav-link :href="$navLink('student.mess.accounts.index')" icon="landmark" :label="__('student.nav.accounts')" :active="request()->routeIs('student.mess.accounts.*')" />
                        <x-student.nav-link :href="$navLink('student.mess.transactions.index')" icon="arrow-left-right" :label="__('student.nav.transactions')" :active="request()->routeIs('student.mess.transactions.*')" />
                    @endif
                    <x-student.nav-link :href="$navLink('student.mess.settlements.index')" icon="calendar-check" :label="__('student.nav.settlements')" :active="request()->routeIs('student.mess.settlements.*')" />
                    <x-student.nav-link :href="$navLink('student.mess.reports.index')" icon="bar-chart-3" :label="__('student.nav.reports')" :active="request()->routeIs('student.mess.reports.*')" />
                </div>
            </div>

            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600" :class="collapsed ? 'md:hidden' : ''">{{ __('student.nav.settings') }}</p>
                <div class="space-y-0.5">
                    @if($isMessOwner)
                        <x-student.nav-link :href="$navLink('student.mess.settings.edit')" icon="settings" :label="__('student.nav.mess_settings')" :active="request()->routeIs('student.mess.settings.*')" />
                        <x-student.nav-link :href="$navLink('student.mess.permissions.index')" icon="shield-check" :label="__('student.nav.member_permissions')" :active="request()->routeIs('student.mess.permissions.*')" />
                    @endif
                    <x-student.nav-link :href="route('student.profile.edit')" icon="user-cog" :label="__('student.nav.profile')" :active="request()->routeIs('student.profile.*')" />
                </div>
            </div>

            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600" :class="collapsed ? 'md:hidden' : ''">{{ __('student.nav.support') }}</p>
                <div class="space-y-0.5">
                    <x-student.nav-link :href="route('tickets.index')" icon="life-buoy" :label="__('student.nav.tickets')" :active="request()->routeIs('tickets.*')" :badge="$unreadTicketCount" />
                    <x-student.nav-link :href="route('notifications.index')" icon="bell" :label="__('student.nav.notifications')" :active="request()->routeIs('notifications.*')" :badge="$unreadNotificationCount" />
                </div>
            </div>
        </nav>

        <div class="border-t border-white/10 p-2">
            <div class="glass mb-2 rounded-xl p-3" :class="collapsed ? 'md:hidden' : ''">
                <p class="text-[10px] text-gray-500 font-bn">{{ __('student.nav.logged_in_as') }}</p>
                <p class="truncate text-sm font-bold font-bn">{{ $studentUser->name }}</p>
                <p class="text-[10px] uppercase tracking-wide text-emerald-400">{{ $navMember ? __('student.members.roles.'.$navMember->role) : __('student.nav.student_product') }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-500/10 py-2 text-xs text-red-300 transition hover:bg-red-500/20 font-bn" :class="collapsed ? 'md:px-0' : ''">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span :class="collapsed ? 'md:hidden' : ''">{{ __('student.nav.logout') }}</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-[#050B14]/85 px-3 py-2.5 backdrop-blur-xl sm:px-5">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <button @click="sidebarOpen = true" class="glass flex h-9 w-9 items-center justify-center rounded-lg md:hidden" aria-label="{{ __('student.nav.open_menu') }}">
                    <i data-lucide="menu" class="h-4 w-4"></i>
                </button>
                <button @click="toggleCollapsed()" class="glass hidden h-9 w-9 items-center justify-center rounded-lg md:flex" :aria-label="collapsed ? @js(__('student.nav.expand_sidebar')) : @js(__('student.nav.collapse_sidebar'))">
                    <i x-show="!collapsed" data-lucide="panel-left-close" class="h-4 w-4"></i>
                    <i x-show="collapsed" x-cloak data-lucide="panel-left-open" class="h-4 w-4"></i>
                </button>

                <div class="hidden min-w-0 flex-1 sm:block">
                    <nav class="flex min-w-0 items-center gap-1.5 text-[11px] text-gray-500 font-bn" aria-label="Breadcrumb">
                        <span class="truncate">{{ __('student.nav.breadcrumb_root') }}</span>
                        <i data-lucide="chevron-right" class="h-3 w-3 shrink-0"></i>
                        <span class="truncate text-gray-300">@yield('breadcrumb', __('student.nav.dashboard'))</span>
                    </nav>
                </div>

                <div class="ml-auto flex items-center gap-1.5">
                    @if($navMess)
                        <a href="{{ route('student.messes.index') }}" class="hidden items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2.5 py-2 text-[11px] text-gray-300 transition hover:bg-white/10 lg:flex font-bn">
                            <i data-lucide="chevrons-up-down" class="h-3.5 w-3.5"></i>
                            <span class="max-w-[120px] truncate">{{ $navMess->name }}</span>
                        </a>
                    @endif

                    <button @click="toggleDark()" class="glass flex h-9 w-9 items-center justify-center rounded-full transition hover:scale-105" aria-label="{{ __('student.nav.toggle_theme') }}">
                        <i x-show="!dark" data-lucide="moon" class="h-4 w-4"></i>
                        <i x-show="dark" x-cloak data-lucide="sun" class="h-4 w-4"></i>
                    </button>

                    @include('partials.language-switcher', ['compact' => true])

                    <x-notifications.bell :count="$unreadNotificationCount" />

                    <div class="relative" x-data="{ profileOpen: false }" @click.outside="profileOpen = false" @keydown.escape.window="profileOpen = false">
                        <button @click="profileOpen = !profileOpen" class="glass flex items-center gap-2 rounded-full p-1 pr-2 transition hover:bg-white/10" :aria-expanded="profileOpen ? 'true' : 'false'">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/20 text-[11px] font-bold text-emerald-300">{{ mb_strtoupper(mb_substr($studentUser->name, 0, 1)) }}</span>
                            <span class="hidden max-w-[110px] truncate text-xs font-bold font-bn sm:block">{{ $studentUser->name }}</span>
                            <i data-lucide="chevron-down" class="h-3 w-3 text-gray-400"></i>
                        </button>

                        <div x-show="profileOpen" x-cloak x-transition.origin.top.right class="absolute right-0 z-[80] mt-2 w-64 overflow-hidden rounded-xl border border-white/10 bg-[#081510] shadow-2xl">
                            <div class="border-b border-white/10 px-4 py-3">
                                <p class="truncate text-sm font-bold font-bn text-white">{{ $studentUser->name }}</p>
                                <p class="truncate text-[11px] text-gray-500">{{ $studentUser->email }}</p>
                                <p class="mt-1 inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-300 font-bn">{{ $navMember ? __('student.members.roles.'.$navMember->role) : __('student.nav.student_product') }}</p>
                            </div>
                            <a href="{{ route('student.profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-300 hover:bg-white/5 font-bn">
                                <i data-lucide="user-cog" class="h-4 w-4"></i>{{ __('student.nav.profile') }}
                            </a>
                            <a href="{{ route('student.subscription.show') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-300 hover:bg-white/5 font-bn">
                                <i data-lucide="badge-check" class="h-4 w-4"></i>{{ __('student.nav.subscription') }}
                            </a>
                            <a href="{{ route('student.messes.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-300 hover:bg-white/5 font-bn">
                                <i data-lucide="layout-grid" class="h-4 w-4"></i>{{ __('student.nav.workspaces') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-xs text-red-300 hover:bg-red-500/10 font-bn">
                                    <i data-lucide="log-out" class="h-4 w-4"></i>{{ __('student.nav.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
</body>
</html>
