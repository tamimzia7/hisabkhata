@props(['count' => 0, 'showDropdown' => false])

<button
    type="button"
    class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm"
    :class="page === 'notifications' ? 'nav-active' : 'text-gray-400'"
    title="{{ __('notifications.sidebar_label') }}"
    @click="setPage('notifications')"
>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm6-14a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span class="nav-label">{{ __('notifications.sidebar_label') }}</span>
    @if($count > 0)
        <span class="ml-auto text-xs font-bold font-bn text-emerald-400 bg-emerald-500/15 px-2 py-0.5 rounded-full">
            {{ $count }}
        </span>
    @endif
</button>

@if($showDropdown)
<div
    x-show="sidebarOpen"
    x-cloak
    class="absolute right-0 mt-2 glass rounded-xl shadow-2xl w-80 z-50 overflow-hidden border border-white/10"
>
    <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
        <h3 class="text-sm font-bold font-bn text-white">{{ __('notifications.dashboard') }}</h3>
        <span
            x-show="unread > 0"
            class="text-xs font-bn font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full"
            x-text="unread + ' unread'"
        ></span>
    </div>

    <div class="max-h-64 overflow-y-auto">
        <template x-if="notifications.length === 0">
            <div class="px-4 py-6 text-center text-sm text-gray-400 font-bn">{{ __('notifications.no_notifications') }}</div>
        </template>
        <template x-for="(notification, index) in notifications" :key="index">
            <a
                :href="notification.action_url || '#'"
                class="flex items-start gap-3 px-4 py-3 hover:bg-white/5 transition text-sm"
                :class="notification.read ? 'opacity-60' : 'bg-emerald-500/5'"
            >
                <span
                    x-show="! notification.read"
                    class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"
                ></span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold font-bn text-white truncate" x-text="notification.title"></p>
                    <p class="text-xs text-gray-400 mt-0.5 truncate" x-text="notification.body"></p>
                    <p class="text-[10px] text-gray-500 mt-1 font-bn" x-text="formatDate(notification.date)"></p>
                </div>
            </a>
        </template>
    </div>
</div>
@endif
