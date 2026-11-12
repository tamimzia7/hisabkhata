@php
    $user = auth()->user();
    $unreadNotificationCount ??= $user?->unreadNotifications()->count() ?? 0;
    $ticketUnreadCount ??= $user ? \App\Models\Ticket::unreadTicketCountFor($user) : 0;
    $navItem = fn (bool $active): string => $active
        ? 'border-emerald-400/20 bg-emerald-500/15 text-emerald-200 shadow-sm'
        : 'border-transparent text-gray-300 hover:border-white/10 hover:bg-white/5 hover:text-white';
    $navIcon = 'h-4 w-4 shrink-0 transition-colors';
@endphp

<aside class="hidden md:flex w-64 flex-col glass-strong m-3 rounded-2xl p-3 sticky top-3 h-[calc(100vh-1.5rem)] overflow-hidden">
    <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2 transition hover:bg-white/5">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-300 to-emerald-600 text-base font-bold text-black shadow-lg shadow-emerald-950/30">৳</span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-bold font-bn text-white">{{ __('site.common.brand') }}</span>
            <span class="block text-[10px] uppercase tracking-[0.22em] text-emerald-400">Dashboard</span>
        </span>
    </a>

    <nav class="mt-4 flex-1 space-y-1.5 overflow-y-auto text-sm font-bn">
        <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500">{{ __('site.dashboard.dashboard') }}</p>

        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2.5 transition {{ $navItem(request()->routeIs('dashboard')) }}">
            <i data-lucide="layout-dashboard" class="{{ $navIcon }}"></i>
            <span>{{ __('site.dashboard.dashboard') }}</span>
        </a>

        <a href="{{ route('subscriptions.index') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2.5 transition {{ $navItem(request()->routeIs('subscriptions.*')) }}">
            <i data-lucide="credit-card" class="{{ $navIcon }}"></i>
            <span>{{ __('subscription.title') }}</span>
        </a>

        @if($user?->role === \App\Models\User::ROLE_STUDENT)
            <a href="{{ route('student.dashboard') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-2.5 transition {{ $navItem(request()->routeIs('student.*')) }}">
                <i data-lucide="utensils-crossed" class="{{ $navIcon }}"></i>
                <span>{{ __('student.nav.module_link') }}</span>
            </a>
        @endif

        <p class="px-3 pb-1 pt-3 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500">{{ __('tickets.support') }}</p>

        <div x-data="{ open: @js(request()->routeIs('tickets.*')) }">
            <button type="button" @click="open = !open"
                    :class="open ? 'text-white' : 'text-gray-300'"
                    class="flex w-full items-center justify-between gap-2 rounded-xl border border-transparent px-3 py-2.5 font-bn transition hover:border-white/10 hover:bg-white/5">
                <span class="flex items-center gap-3">
                    <i data-lucide="life-buoy" class="{{ $navIcon }}"></i>
                    <span>{{ __('tickets.support') }}</span>
                </span>
                <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="open" x-collapse x-cloak class="nav-sublist mt-1 ml-3 space-y-1 border-l border-white/10 pl-2">
                <a href="{{ route('tickets.index') }}"
                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bn transition-colors {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.show') ? 'bg-emerald-500/15 text-emerald-300' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                    <i data-lucide="ticket" class="h-4 w-4 shrink-0"></i>
                    <span>{{ __('tickets.title') }}</span>
                    @if($ticketUnreadCount > 0)
                        <span class="ml-auto rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-400">{{ $ticketUnreadCount > 99 ? '99+' : localize_digits($ticketUnreadCount) }}</span>
                    @endif
                </a>
            </div>
        </div>

        <x-notifications.sidebar-link :count="$unreadNotificationCount" variant="client" />
    </nav>

    <div class="mt-auto space-y-2 border-t border-white/10 pt-3">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-sm font-bold text-emerald-300">
                    {{ mb_strtoupper(mb_substr($user?->name ?? '', 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold font-bn text-white">{{ $user?->name }}</p>
                    <p class="truncate text-[10px] text-gray-500">{{ $user?->email }}</p>
                </div>
            </div>
            <span class="mt-2 inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-300 font-bn">
                {{ __('site.auth.'.$user?->role) }}
            </span>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2.5 text-sm text-red-300 transition hover:border-red-400/30 hover:bg-red-500/20 hover:text-red-200 font-bn">
                <i data-lucide="log-out" class="h-4 w-4"></i>
                <span>{{ __('site.dashboard.logout') }}</span>
            </button>
        </form>
    </div>
</aside>
