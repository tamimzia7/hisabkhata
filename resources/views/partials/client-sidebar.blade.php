@php($user = auth()->user())
@php($unreadNotificationCount ??= $user?->unreadNotifications()->count() ?? 0)

<aside class="hidden md:flex w-64 flex-col glass-strong m-3 rounded-2xl p-4 sticky top-3 h-[calc(100vh-24px)]">
    <a href="{{ route('home') }}" class="flex items-center gap-2 mb-6 px-2">
        <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center font-bold text-black">৳</div>
        <div><div class="text-sm font-bold font-bn">{{ __('site.common.brand') }}</div><div class="text-[9px] tracking-widest text-emerald-400">DASHBOARD</div></div>
    </a>
    <nav class="space-y-1 text-sm font-bn">
        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-300 hover:bg-white/5' }}">{{ __('site.dashboard.dashboard') }}</a>
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
