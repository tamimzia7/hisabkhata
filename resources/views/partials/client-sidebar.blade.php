@php($user = auth()->user())
@php($unreadNotificationCount ??= $user?->unreadNotifications()->count() ?? 0)
@php($ticketUnreadCount ??= $user ? \App\Models\Ticket::unreadTicketCountFor($user) : 0)

<aside class="hidden md:flex w-64 flex-col glass-strong m-3 rounded-2xl p-4 sticky top-3 h-[calc(100vh-24px)]">
    <a href="{{ route('home') }}" class="flex items-center gap-2 mb-6 px-2">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center font-bold text-black">৳</div>
        <div><div class="text-sm font-bold font-bn">{{ __('site.common.brand') }}</div><div class="text-[9px] tracking-widest text-emerald-400">DASHBOARD</div></div>
    </a>
    <nav class="space-y-1 text-sm font-bn">
        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-300 hover:bg-white/5' }}">{{ __('site.dashboard.dashboard') }}</a>
        <a href="{{ route('subscriptions.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('subscriptions.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-300 hover:bg-white/5' }}">{{ __('subscription.title') }}</a>

        @if($user?->role === \App\Models\User::ROLE_STUDENT)
            <a href="{{ route('student.dashboard') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('student.*') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-300 hover:bg-white/5' }}">{{ __('student.nav.module_link') }}</a>
        @endif

        <div x-data="{ open: @js(request()->routeIs('tickets.*')) }">
            <button type="button" @click="open = !open"
                    :class="open ? 'text-white' : 'text-gray-300'"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg font-bn hover:bg-white/5 transition">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 18v-6a9 9 0 0118 0v6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/></svg>
                    <span>{{ __('tickets.support') }}</span>
                </span>
                <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" x-collapse x-cloak class="nav-sublist mt-1 ml-3 space-y-1 border-l border-white/10 pl-2">
                <a href="{{ route('tickets.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-bn transition-colors {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.show') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-300 hover:bg-white/5' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    <span>{{ __('tickets.title') }}</span>
                    @if($ticketUnreadCount > 0)
                        <span class="ml-auto rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-400">{{ $ticketUnreadCount > 99 ? '99+' : localize_digits($ticketUnreadCount) }}</span>
                    @endif
                </a>
            </div>
        </div>

        <x-notifications.sidebar-link :count="$unreadNotificationCount" variant="client" />

        <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">{{ __('site.dashboard.entries') }}</a>
        <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">{{ __('site.dashboard.reports') }}</a>
        <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">{{ __('site.dashboard.settings') }}</a>
    </nav>
    <div class="mt-auto">
        <div class="glass rounded-xl p-3 mb-3">
            <div class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.logged_in_as') }}</div>
            <div class="text-sm font-bold font-bn">{{ $user->name }}</div>
            <div class="text-[10px] text-emerald-400 uppercase">{{ __('site.auth.'.$user->role) }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full glass rounded-lg py-2 text-sm text-red-300 hover:bg-red-500/10 font-bn">{{ __('site.dashboard.logout') }}</button>
        </form>
    </div>
</aside>
