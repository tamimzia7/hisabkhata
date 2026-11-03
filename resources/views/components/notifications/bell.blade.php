@props(['count' => null])

@php
    $unread = $count ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
@endphp

<div
    x-data="hisabNotificationBell({
        initialCount: @js((int) $unread),
        feedUrl: @js(route('notifications.get')),
        markUrl: @js(route('notifications.mark')),
        markAllUrl: @js(route('notifications.mark-all-read')),
        csrf: @js(csrf_token()),
    })"
    x-init="init()"
    x-on:click.outside="close()"
    x-on:keydown.escape.window="close()"
    class="relative"
>
    <button
        type="button"
        x-on:click="toggle()"
        :aria-expanded="isOpen ? 'true' : 'false'"
        aria-haspopup="true"
        aria-label="{{ __('notifications.bell_label') }}"
        title="{{ __('notifications.bell_label') }}"
        class="relative glass w-9 h-9 rounded-lg flex items-center justify-center shrink-0 hover:scale-110 transition"
    >
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        <span
            x-show="unread > 0"
            x-cloak
            class="absolute -top-0.5 -right-0.5 glass-strong flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[11px] font-bold text-white leading-tight rounded-full bg-red-500"
            x-text="unread > 99 ? '99+' : unread"
        ></span>
    </button>

    <div
        x-show="isOpen"
        x-cloak
        x-transition.origin.top.right
        class="absolute right-0 mt-2 glass-strong rounded-xl shadow-2xl w-80 max-w-[calc(100vw-2rem)] z-[80] overflow-hidden border border-white/10"
        role="menu"
        aria-label="{{ __('notifications.dashboard') }}"
    >
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-white/10">
            <h3 class="text-sm font-bold font-bn text-white">{{ __('notifications.dashboard') }}</h3>
            <button
                type="button"
                x-show="unread > 0"
                x-on:click="markAll()"
                class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 font-bn"
            >{{ __('notifications.mark_all_read') }}</button>
        </div>

        <div class="max-h-72 overflow-y-auto">
            <template x-if="loading && notifications.length === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-400 font-bn">{{ __('notifications.loading') }}</div>
            </template>

            <template x-if="! loading && notifications.length === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-400 font-bn">{{ __('notifications.no_notifications') }}</div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <a
                    :href="notification.url || '#'"
                    x-on:click="handleClick($event, notification)"
                    class="flex items-start gap-3 px-4 py-3 hover:bg-white/5 transition text-sm"
                    :class="notification.read ? 'opacity-60' : 'bg-emerald-500/5'"
                    role="menuitem"
                >
                    <span
                        x-show="! notification.read"
                        class="w-1.5 h-1.5 mt-2 rounded-full bg-emerald-400 shrink-0"
                    ></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold font-bn text-white truncate" x-text="notification.title"></p>
                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-2 font-bn" x-text="notification.body"></p>
                        <p class="text-[10px] text-gray-500 mt-1 font-bn" x-text="notification.created_at_human"></p>
                    </div>
                </a>
            </template>
        </div>

        <a
            href="{{ route('notifications.index') }}"
            class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-bn text-emerald-400 hover:bg-white/5 transition border-t border-white/10"
        >
            {{ __('notifications.view_all') }}
        </a>
    </div>
</div>

@once
<script>
    function hisabNotificationBell(config) {
        return {
            isOpen: false,
            loading: false,
            unread: Number(config.initialCount || 0),
            notifications: [],
            timer: null,

            init() {
                this.load();

                this.timer = setInterval(() => this.load(), 60000);
            },

            destroy() {
                if (this.timer) {
                    clearInterval(this.timer);
                }
            },

            toggle() {
                this.isOpen = ! this.isOpen;

                if (this.isOpen) {
                    this.load();
                }
            },

            close() {
                this.isOpen = false;
            },

            load() {
                this.loading = true;

                fetch(config.feedUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                })
                    .then((response) => response.ok ? response.json() : Promise.reject())
                    .then((data) => {
                        this.notifications = data.notifications || [];
                        this.unread = Number(data.unread || 0);
                    })
                    .catch(() => {})
                    .finally(() => {
                        this.loading = false;
                    });
            },

            handleClick(event, notification) {
                if (notification.url) {
                    return;
                }

                event.preventDefault();

                if (! notification.read) {
                    this.markRead(notification);
                }
            },

            markRead(notification) {
                fetch(config.markUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ notification_id: notification.id }),
                })
                    .then((response) => response.ok ? response.json() : Promise.reject())
                    .then((data) => {
                        notification.read = true;
                        this.unread = Number(data.unread ?? Math.max(0, this.unread - 1));
                    })
                    .catch(() => {});
            },

            markAll() {
                fetch(config.markAllUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => response.ok ? response.json() : Promise.reject())
                    .then(() => {
                        this.unread = 0;
                        this.notifications = this.notifications.map((notification) => ({
                            ...notification,
                            read: true,
                        }));
                    })
                    .catch(() => {});
            },
        };
    }
</script>
@endonce
