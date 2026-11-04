@props(['max' => 'max-w-5xl', 'back' => null, 'backLabel' => null])

@php($user = auth()->user())
@php($ticketUnreadCount = \App\Models\Ticket::unreadTicketCountFor($user))

<div class="min-h-screen flex relative z-10">
    @include('partials.client-sidebar')

    <main class="flex-1 p-3 md:p-6 overflow-x-hidden min-w-0">
        <header class="max-w-5xl mx-auto glass-strong rounded-2xl p-3 sm:p-4 mb-4 sm:mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
                <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center font-bold text-black shrink-0">৳</div>
                <div class="min-w-0">
                    <div class="text-sm font-bold font-bn truncate">{{ __('site.common.brand') }}</div>
                    <div class="text-[9px] tracking-widest text-emerald-400">DASHBOARD</div>
                </div>
            </a>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('tickets.index') }}"
                   class="relative glass w-9 h-9 rounded-lg flex items-center justify-center hover:scale-110 transition shrink-0"
                   title="{{ __('tickets.my_tickets') }}" aria-label="{{ __('tickets.my_tickets') }}">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    @if($ticketUnreadCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-bold leading-tight text-white">
                            {{ $ticketUnreadCount > 99 ? '99+' : localize_digits($ticketUnreadCount) }}
                        </span>
                    @endif
                </a>
                <x-notifications.bell :count="$user->unreadNotifications()->count()" />
                <button @click="toggleDark()" class="w-9 h-9 glass rounded-lg flex items-center justify-center hover:scale-110 transition shrink-0" aria-label="Toggle theme">
                    <span x-show="!dark">🌙</span><span x-show="dark">☀️</span>
                </button>
                @include('partials.language-switcher')
                <form method="POST" action="{{ route('logout') }}" class="md:hidden">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="glass px-3 py-2 rounded-lg text-xs sm:text-sm font-bn text-red-300 hover:bg-red-500/10">
                        {{ __('site.dashboard.logout') }}
                    </button>
                </form>
            </div>
        </header>

        <div class="{{ $max }} mx-auto">
            @if($back)
                <a href="{{ $back }}" class="inline-flex items-center gap-1.5 text-sm text-emerald-300 hover:text-emerald-200 font-bn mb-4">
                    ← {{ $backLabel ?? __('tickets.show.back_to_tickets') }}
                </a>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
