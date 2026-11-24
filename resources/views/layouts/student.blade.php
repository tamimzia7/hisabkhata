@php
    $studentUser = auth()->user();
    $unreadNotificationCount = $studentUser?->unreadNotifications()->count() ?? 0;
    $unreadTicketCount = $studentUser ? \App\Models\Ticket::unreadTicketCountFor($studentUser) : 0;
    $navMess = $navMess ?? null;
    $navMember = $navMess && $studentUser ? $navMess->memberFor($studentUser) : null;
    $isMessOwner = $navMess && $studentUser && $navMess->owner_id === $studentUser->id;
    $can = fn (string $ability): bool => (bool) ($navMember?->allows($ability));
    $navLink = fn (string $name): string => $navMess ? route($name, $navMess) : route('student.messes.index');
    $activeProductNavigation = collect($clientNavigation['products'] ?? [])->firstWhere('active', true) ?? ['main' => [], 'settings' => []];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    dark: window.hisabTheme ? window.hisabTheme.isDark() : true,
    toggleDark() {
        window.hisabTheme.toggle();
        this.dark = window.hisabTheme.isDark();
    },
    init() {
        try {
            this.collapsed = JSON.parse(localStorage.getItem('student-sidebar-collapsed') ?? 'false');
        } catch (error) {
            this.collapsed = false;
        }

        window.addEventListener('hisab-theme-changed', (event) => {
            this.dark = event.detail.dark;
        });
    },
    sidebarOpen: false,
    collapsed: false,
    toggleCollapsed() {
        this.collapsed = !this.collapsed;

        try {
            localStorage.setItem('student-sidebar-collapsed', JSON.stringify(this.collapsed));
        } catch (error) {
            // Keep the sidebar usable when storage is unavailable.
        }
    },
    toastMsg: window.__flashToast || '',
    showToast: Boolean(window.__flashToast),
}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<title>@yield('title', __('student.brand'))</title>
@include('partials.theme-init')
<script>window.__flashToast = @js(session('toast'));</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    if (window.tailwind) {
        tailwind.config = Object.assign({}, tailwind.config, { darkMode: 'class' });
    }
</script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --brand-bg: #050B14;
        --brand-surface: #091C14;
        --brand-text: #f8fafc;
        --brand-sidebar: rgba(5, 11, 20, 0.95);
        --brand-topbar: rgba(5, 11, 20, 0.85);
        --brand-popover: #081510;
        --brand-border: rgba(255, 255, 255, 0.10);
        --brand-hover: rgba(255, 255, 255, 0.05);
        --brand-input: rgba(0, 0, 0, 0.30);
        color-scheme: dark;
    }
    html:not(.dark) {
        --ui-bg: #F8FAFC;
        --ui-surface: #FFFFFF;
        --ui-muted: #F1F5F9;
        --ui-text: #0F172A;
        --ui-secondary: #64748B;
        --ui-navy: #1E3A5F;
        --ui-blue: #2563EB;
        --ui-border: #E2E8F0;
        --ui-success: #0D9488;
        --ui-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        --ui-shadow-md: 0 8px 24px rgba(15, 23, 42, 0.06);
        --brand-bg: var(--ui-bg);
        --brand-surface: var(--ui-surface);
        --brand-text: var(--ui-text);
        --brand-sidebar: var(--ui-surface);
        --brand-topbar: var(--ui-surface);
        --brand-popover: var(--ui-surface);
        --brand-border: var(--ui-border);
        --brand-hover: var(--ui-muted);
        --brand-input: var(--ui-surface);
        color-scheme: light !important;
    }
    html { -webkit-text-size-adjust: 100%; font-size: 18px; }
    .text-\[8px\] { font-size: 10px !important; }
    .text-\[9px\] { font-size: 11px !important; }
    .text-\[10px\] { font-size: 12px !important; }
    .text-\[11px\] { font-size: 13px !important; }
    .text-\[13px\] { font-size: 15px !important; }
    body { font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; background: var(--brand-bg); color: var(--brand-text); }
    .student-sidebar { background-color: var(--brand-sidebar); }
    .student-topbar { background-color: var(--brand-topbar); }
    .student-popover { background-color: var(--brand-popover); }
    .font-bn { font-family: 'Hind Siliguri', sans-serif; }
    .font-display { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; letter-spacing: -0.03em; }
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
    input[type="checkbox"], input[type="radio"], input[type="range"], progress { accent-color: #10B981; }
    .kpi-label { font-size: 0.7rem; color: #94a3b8; }
    .table-wrap { overflow-x: auto; }
    .data-table { width: 100%; min-width: 640px; font-size: 0.8rem; border-collapse: collapse; }
    .data-table th { text-align: left; color: #94a3b8; font-weight: 600; padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); white-space: nowrap; }
    .data-table td { padding: 0.55rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
    .data-table tbody tr:hover { background: rgba(255,255,255,0.03); }
    .card-hover { transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .card-hover:hover { transform: translateY(-2px); border-color: rgba(52,211,153,0.4); box-shadow: 0 18px 45px -18px rgba(16,185,129,0.45); }
    .bg-emerald-500.text-white { color: #04120B; }

    html:not(.dark) body { background: var(--ui-bg) !important; color: var(--ui-text) !important; }
    html:not(.dark) body,
    html:not(.dark) body * { font-weight: 400 !important; }
    html:not(.dark) body h1,
    html:not(.dark) body h2,
    html:not(.dark) body h3,
    html:not(.dark) body h4,
    html:not(.dark) body h5,
    html:not(.dark) body h6,
    html:not(.dark) body th,
    html:not(.dark) body strong,
    html:not(.dark) body b,
    html:not(.dark) body .font-display,
    html:not(.dark) body .font-bold,
    html:not(.dark) body .font-semibold { font-weight: 600 !important; }
    html:not(.dark) body .font-medium { font-weight: 500 !important; }
    html:not(.dark) body .font-extrabold,
    html:not(.dark) body .font-black { font-weight: 700 !important; }
    html:not(.dark) ::selection { background: #DBEAFE; color: var(--ui-navy); }
    html:not(.dark) .glass,
    html:not(.dark) .glass-strong,
    html:not(.dark) .glass-nav,
    html:not(.dark) .student-sidebar,
    html:not(.dark) .student-topbar,
    html:not(.dark) .student-popover,
    html:not(.dark) .admin-bg,
    html:not(.dark) .admin-surface,
    html:not(.dark) [role="dialog"],
    html:not(.dark) [role="menu"] { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; box-shadow: var(--ui-shadow) !important; }
    html:not(.dark) .card-hover,
    html:not(.dark) .card-3d { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; box-shadow: var(--ui-shadow) !important; }
    html:not(.dark) .card-hover:hover,
    html:not(.dark) .card-3d:hover { transform: none !important; border-color: var(--ui-border) !important; box-shadow: var(--ui-shadow-md) !important; }
    html:not(.dark) .text-white,
    html:not(.dark) .text-gray-200,
    html:not(.dark) .text-gray-300 { color: var(--ui-text) !important; }
    html:not(.dark) .text-gray-400,
    html:not(.dark) .text-gray-500,
    html:not(.dark) .text-gray-600 { color: var(--ui-secondary) !important; }
    html:not(.dark) .text-emerald-200,
    html:not(.dark) .text-emerald-300,
    html:not(.dark) .text-emerald-400,
    html:not(.dark) .text-emerald-500 { color: var(--ui-navy) !important; }
    html:not(.dark) .text-teal-300,
    html:not(.dark) .text-teal-400,
    html:not(.dark) .text-teal-500 { color: var(--ui-success) !important; }
    html:not(.dark) .text-sky-300,
    html:not(.dark) .text-sky-400,
    html:not(.dark) .text-blue-400,
    html:not(.dark) .text-blue-500 { color: var(--ui-blue) !important; }
    html:not(.dark) .text-orange-300,
    html:not(.dark) .text-orange-400,
    html:not(.dark) .text-amber-300,
    html:not(.dark) .text-amber-400 { color: #B45309 !important; }
    html:not(.dark) .text-red-300,
    html:not(.dark) .text-red-400 { color: #B91C1C !important; }
    html:not(.dark) .text-lime-300,
    html:not(.dark) .text-lime-400 { color: var(--ui-success) !important; }
    html:not(.dark) .text-violet-300 { color: #6D28D9 !important; }
    html:not(.dark) .hover\:text-white:hover,
    html:not(.dark) .hover\:text-emerald-300:hover { color: var(--ui-navy) !important; }
    html:not(.dark) aside a:hover,
    html:not(.dark) nav a:hover { color: var(--ui-navy) !important; }
    html:not(.dark) .btn-green,
    html:not(.dark) .btn-emerald,
    html:not(.dark) .tab-btn.active,
    html:not(.dark) .bg-emerald-500,
    html:not(.dark) .bg-emerald-600,
    html:not(.dark) .bg-emerald-700 { background-color: var(--ui-navy) !important; background-image: none !important; border-color: var(--ui-navy) !important; box-shadow: none !important; color: #FFFFFF !important; }
    html:not(.dark) .btn-green:hover,
    html:not(.dark) .btn-emerald:hover,
    html:not(.dark) .hover\:bg-emerald-400:hover,
    html:not(.dark) .hover\:bg-emerald-500:hover { background-color: var(--ui-blue) !important; box-shadow: var(--ui-shadow) !important; color: #FFFFFF !important; transform: none !important; }
    html:not(.dark) .bg-emerald-400,
    html:not(.dark) .bg-teal-500,
    html:not(.dark) .bg-teal-600 { background-color: var(--ui-success) !important; box-shadow: none !important; color: #FFFFFF !important; }
    html:not(.dark) .bg-emerald-400\/10,
    html:not(.dark) .bg-emerald-500\/10,
    html:not(.dark) .bg-emerald-500\/15,
    html:not(.dark) .bg-emerald-500\/20,
    html:not(.dark) .bg-teal-500\/10 { background-color: var(--ui-muted) !important; }
    html:not(.dark) .btn-ghost,
    html:not(.dark) .btn-secondary { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; color: var(--ui-navy) !important; }
    html:not(.dark) .btn-ghost:hover,
    html:not(.dark) .btn-secondary:hover { background-color: var(--ui-muted) !important; }
    html:not(.dark) input,
    html:not(.dark) select,
    html:not(.dark) textarea,
    html:not(.dark) .field { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; box-shadow: none !important; color: var(--ui-text) !important; }
    html:not(.dark) input::placeholder,
    html:not(.dark) textarea::placeholder,
    html:not(.dark) .field::placeholder { color: var(--ui-secondary) !important; }
    html:not(.dark) input:focus,
    html:not(.dark) select:focus,
    html:not(.dark) textarea:focus,
    html:not(.dark) .field:focus { border-color: var(--ui-blue) !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important; outline: none !important; }
    html:not(.dark) option { background-color: var(--ui-surface) !important; color: var(--ui-text) !important; }
    html:not(.dark) input[type="checkbox"],
    html:not(.dark) input[type="radio"],
    html:not(.dark) input[type="range"],
    html:not(.dark) progress { accent-color: var(--ui-blue) !important; }
    html:not(.dark) .\[color-scheme\:dark\] { color-scheme: light !important; }
    html:not(.dark) .focus\:border-emerald-500:focus,
    html:not(.dark) .focus\:border-emerald-500\/60:focus { border-color: var(--ui-blue) !important; }
    html:not(.dark) .focus\:ring-emerald-400\/50:focus,
    html:not(.dark) .focus\:ring-emerald-500\/40:focus,
    html:not(.dark) .focus\:ring-emerald-500\/60:focus,
    html:not(.dark) .ring-emerald-400\/40 { --tw-ring-color: rgba(37, 99, 235, 0.25) !important; }
    html:not(.dark) table { background-color: var(--ui-surface) !important; }
    html:not(.dark) thead th,
    html:not(.dark) .data-table th { background-color: var(--ui-muted) !important; border-bottom-color: var(--ui-border) !important; color: var(--ui-secondary) !important; }
    html:not(.dark) tbody td,
    html:not(.dark) .data-table td { border-bottom-color: var(--ui-border) !important; color: var(--ui-text) !important; }
    html:not(.dark) tbody tr:hover,
    html:not(.dark) .data-table tbody tr:hover { background-color: var(--ui-muted) !important; }
    html:not(.dark) .bg-black\/20,
    html:not(.dark) .bg-black\/30,
    html:not(.dark) .bg-black\/40,
    html:not(.dark) .bg-emerald-500\/5,
    html:not(.dark) .bg-white\/5,
    html:not(.dark) .bg-white\/10,
    html:not(.dark) .bg-\[\#050B14\],
    html:not(.dark) .bg-\[\#06130D\],
    html:not(.dark) .bg-\[\#091C14\] { background-color: var(--ui-muted) !important; }
    html:not(.dark) .bg-black\/60 { background-color: rgba(15, 23, 42, 0.24) !important; }
    html:not(.dark) .bg-red-500\/10 { background-color: #FEF2F2 !important; }
    html:not(.dark) .border-emerald-400\/20,
    html:not(.dark) .border-emerald-400\/25,
    html:not(.dark) .border-emerald-400\/30,
    html:not(.dark) .border-emerald-400\/40,
    html:not(.dark) .border-emerald-400\/60,
    html:not(.dark) .border-emerald-500\/10,
    html:not(.dark) .border-emerald-500\/15,
    html:not(.dark) .border-emerald-500\/20,
    html:not(.dark) .border-emerald-500\/25,
    html:not(.dark) .border-emerald-500\/30,
    html:not(.dark) .border-emerald-500\/40,
    html:not(.dark) .hover\:border-emerald-400\/20:hover,
    html:not(.dark) .hover\:border-emerald-400\/25:hover,
    html:not(.dark) .hover\:border-emerald-400\/30:hover,
    html:not(.dark) .hover\:border-emerald-400\/40:hover,
    html:not(.dark) .border-white\/5,
    html:not(.dark) .border-white\/10,
    html:not(.dark) .border-white\/20,
    html:not(.dark) .border-\[\#06130D\] { border-color: var(--ui-border) !important; }
    html:not(.dark) .divide-white\/5 > :not([hidden]) ~ :not([hidden]),
    html:not(.dark) .divide-white\/10 > :not([hidden]) ~ :not([hidden]) { border-color: var(--ui-border) !important; }
    html:not(.dark) .hover\:bg-emerald-400\/10:hover,
    html:not(.dark) .hover\:bg-emerald-500\/5:hover,
    html:not(.dark) .hover\:bg-emerald-500\/10:hover,
    html:not(.dark) .hover\:bg-emerald-500\/15:hover,
    html:not(.dark) .hover\:bg-emerald-500\/20:hover,
    html:not(.dark) .hover\:bg-white\/5:hover,
    html:not(.dark) .hover\:bg-white\/10:hover { background-color: var(--ui-muted) !important; }
    html:not(.dark) .peer:checked ~ .peer-checked\:border-emerald-400\/60 { border-color: var(--ui-blue) !important; }
    html:not(.dark) .peer:checked ~ .peer-checked\:bg-emerald-500\/15 { background-color: #EFF6FF !important; }
    html:not(.dark) .peer:checked ~ .peer-checked\:text-emerald-300 { color: var(--ui-navy) !important; }
    html:not(.dark) .nav-child.active,
    html:not(.dark) .nav-active { background-color: #EFF6FF !important; border-left-color: var(--ui-blue) !important; color: var(--ui-blue) !important; }
    html:not(.dark) [aria-current="page"] { background-color: #EFF6FF !important; border-color: #BFDBFE !important; color: var(--ui-navy) !important; }
    html:not(.dark) .gradient-green,
    html:not(.dark) .gradient-shimmer { background-image: none !important; color: var(--ui-navy) !important; -webkit-text-fill-color: currentColor !important; }
    html:not(.dark) .from-emerald-300,
    html:not(.dark) .from-emerald-400,
    html:not(.dark) .from-emerald-500 { background-image: none !important; background-color: var(--ui-navy) !important; color: #FFFFFF !important; }
    html:not(.dark) .from-emerald-500\/10,
    html:not(.dark) .from-emerald-500\/15,
    html:not(.dark) .via-emerald-500\/5,
    html:not(.dark) .via-emerald-950\/20 { background-image: none !important; background-color: var(--ui-muted) !important; }
    html:not(.dark) .glow-green::before,
    html:not(.dark) .btn-shine::before { display: none !important; }
    html:not(.dark) .shadow-emerald-500\/20,
    html:not(.dark) .shadow-emerald-500\/30,
    html:not(.dark) .shadow-emerald-950\/20,
    html:not(.dark) .shadow-emerald-950\/30 { box-shadow: var(--ui-shadow) !important; }
    html:not(.dark) .cursor { background-color: var(--ui-blue) !important; }
    html:not(.dark) .glass-strong.bg-red-500 { background-color: #DC2626 !important; border-color: #FECACA !important; color: #FFFFFF !important; }
    html:not(.dark) .kpi-label { color: var(--ui-secondary) !important; }
    html:not(.dark) ::-webkit-scrollbar-thumb,
    html:not(.dark) .student-scroll::-webkit-scrollbar-thumb,
    html:not(.dark) .admin-scroll::-webkit-scrollbar-thumb { background-color: #CBD5E1 !important; }
</style>
</head>
<body class="min-h-screen">
<div class="flex min-h-screen">
    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm md:hidden"></div>

    <!-- Sidebar -->
    <aside x-cloak :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0', collapsed ? 'md:w-20' : 'md:w-64']"
           class="student-sidebar fixed md:sticky top-0 left-0 z-40 flex h-screen w-72 shrink-0 -translate-x-full flex-col border-r border-white/10 backdrop-blur-xl transition-all duration-200 md:translate-x-0">
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
                    @foreach($activeProductNavigation['main'] as $link)
                        <x-student.nav-link :href="$link['url']" :icon="$link['icon']" :label="$link['label']" :active="request()->routeIs($link['pattern'])" />
                    @endforeach
                </div>
            </div>

            <div>
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-600" :class="collapsed ? 'md:hidden' : ''">{{ __('student.nav.settings') }}</p>
                <div class="space-y-0.5">
                    @foreach($activeProductNavigation['settings'] as $link)
                        <x-student.nav-link :href="$link['url']" :icon="$link['icon']" :label="$link['label']" :active="request()->routeIs($link['pattern'])" />
                    @endforeach
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
        <header class="student-topbar sticky top-0 z-20 border-b border-white/10 px-3 py-2.5 backdrop-blur-xl sm:px-5">
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

                        <div x-show="profileOpen" x-cloak x-transition.origin.top.right class="student-popover absolute right-0 z-[80] mt-2 w-64 overflow-hidden rounded-xl border border-white/10 shadow-2xl">
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
