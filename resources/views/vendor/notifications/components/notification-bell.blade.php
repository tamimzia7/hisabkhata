@props(['count' => 0])

<div x-data="notificationBell({ initialCount: @js((int) $count) })" x-init="init()" x-on:click.outside="close()">
    <button
        type="button"
        class="relative glass w-9 h-9 rounded-lg flex items-center justify-center shrink-0 hover:scale-110 transition"
        aria-label="{{ __('notifications.bell_label') }}"
    >
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm6-14a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>

        @if($count > 0)
            <span
                class="absolute -top-0.5 -right-0.5 glass-strong flex items-center justify-center min-w-[18px] h-[18px] text-[11px] font-bold text-white leading-tight rounded-full"
                x-text="{{ $count }}"
                :class="unread === 0 ? 'bg-emerald-500 text-black' : 'bg-red-500 text-white'"
            ></span>
        @endif
    </button>

    <template x-if="isOpen">
        <div
            class="absolute right-0 mt-2 glass rounded-xl shadow-2xl w-80 z-50 overflow-hidden border border-white/10"
            x-on:click.self="close()"
            x-cloak
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
                        @click="markRead(notification)"
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
                        <span
                            x-show="! notification.read"
                            class="shrink-0 text-xs font-bold font-bn text-emerald-400"
                            x-text="'MARK READ'"
                        ></span>
                    </a>
                </template>
            </div>

            <a
                href="{{ route('notifications.index') }}"
                class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-bn text-emerald-400 hover:bg-white/5 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                {{ __('notifications.view_all') }}
            </a>
        </div>
    </template>
</div>

@once
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endonce

<script>
function notificationBell(props) {
    return {
        isOpen: false,
        unread: props.initialCount,
        notifications: [],
        timer: null,

        init() {
            this.loadNotifications();
            this.tick();
        },

        loadNotifications() {
            fetch("{{ route('notifications.get') }}")
                .then(r => r.json())
                .then(data => {
                    this.notifications = data.notifications || [];
                    this.unread = data.unread || 0;
                })
                .catch(() => {});
        },

        tick() {
            this.timer = setTimeout(() => {
                this.loadNotifications();
                this.tick();
            }, 30000);
        },

        markRead(notification) {
            if (notification.read) return;
            fetch("{{ route('notifications.mark') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ notification_id: notification.id })
            }).then(() => {
                this.unread--;
                notification.read = true;
            });
        },

        close() {
            this.isOpen = false;
        }
    };
}
</script>
@endonce
