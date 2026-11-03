@extends('layouts.admin')

@section('title', __('notifications.title'))

@section('content')
<div class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold font-bn text-white font-display">{{ __('notifications.title') }}</h1>
            <p class="text-sm text-gray-400 font-bn mt-0.5">
                {{ __('notifications.subtitle', ['unread' => $counts['unread']]) }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('notifications.mark-all-read') }}"
                class="glass px-4 py-2 rounded-lg text-sm font-bn text-white hover:bg-white/5 transition flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                {{ __('notifications.mark_all_read') }}
            </a>
        </div>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-white/5 bg-white/5 flex flex-wrap items-center gap-2">
            <a
                href="{{ route('notifications.index', ['filter' => 'all']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-bn font-bold {{ $filter === 'all' ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5' }} transition"
            >{{ __('notifications.all') }} ({{ $counts['all'] }})</a>
            <a
                href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-bn font-bold {{ $filter === 'unread' ? 'bg-emerald-500/20 text-emerald-400' : 'text-gray-400 hover:bg-white/5' }} transition"
            >{{ __('notifications.unread') }} ({{ $counts['unread'] }})</a>
            <a
                href="{{ route('notifications.index', ['filter' => 'read']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-bn font-bold {{ $filter === 'read' ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5' }} transition"
            >{{ __('notifications.read') }} ({{ $counts['read'] }})</a>
        </div>

        <div class="overflow-x-auto p-4 sm:p-6">
            <template x-if="notifications.length === 0">
                <div class="text-center py-16">
                    <svg class="w-12 h-12 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm6-14a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <p class="text-sm text-gray-400 font-bn">{{ __('notifications.empty') }}</p>
                </div>
            </template>

            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('notifications.notification') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('notifications.action') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('notifications.date') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('notifications.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5" x-ref="notifRows">
                    @foreach($notifications as $notification)
                        <tr
                            class="hover:bg-white/5 transition"
                            data-id="{{ $notification->id }}"
                            data-read="{{ $notification->read_at ? '1' : '0' }}"
                            data-title="{{ mb_encode_numericentity($notification->data['title'] ?? '', [65533 => '&#65533;'], 'UTF-8') }}"
                        >
                            <td class="px-4 sm:px-6 py-4">
                                <div class="min-w-0">
                                    <p class="font-bold text-white font-bn truncate">
                                        {{ $notification->data['title'] ?? __('notifications.unknown') }}
                                        @if($notification->read_at)
                                            <span class="text-[10px] text-gray-500 font-bn">({{ __('notifications.read') }})</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ $notification->data['body'] ?? '' }}
                                    </p>
                                    @if(filled($notification->data['action_url']))
                                        <a
                                            href="{{ $notification->data['action_url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-xs text-emerald-400 hover:underline truncate"
                                        >{{ __('notifications.open') }} ↗</a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                @if(!$notification->read_at)
                                    <button
                                        type="button"
                                        class="text-xs text-emerald-400 hover:text-emerald-300 font-bn px-2"
                                        x-data=""
                                        x-on:click="markRead('{{ $notification->id }}')"
                                    >{{ __('notifications.mark_read') }}</button>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-gray-400 text-xs font-bn">
                                {{ $notification->created_at?->format('d M Y, H:i') ?? '' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <span class="text-xs px-2 py-1 rounded font-bn {{ $notification->read_at ? 'text-gray-500 bg-white/5' : 'text-emerald-400 bg-emerald-500/10' }}">
                                    {{ $notification->read_at ? __('notifications.read') : __('notifications.unread') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    @if($notifications->isNotEmpty())
                        <tr x-show="filtered === 0" x-cloak>
                            <td colspan="4" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('notifications.no_matching') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="px-4 sm:px-6 py-3 border-t border-white/5 bg-white/5 flex justify-between items-center">
            <span class="text-xs text-gray-400 font-bn">
                {{ $notifications->count() }} {{ __('notifications.of') }} {{ $notifications->total() }}
            </span>
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notifications-page', () => ({
        notifications: @json($notifications->items()),
        filter: @js($filter),
        page: @js($notifications->currentPage()),
        perPage: @js($notifications->perPage()),
        total: @js($notifications->total()),

        init() {
            document.querySelectorAll('[data-notif-id]').forEach(el => {
                el.addEventListener('click', () => {
                    this.markRead(el.dataset.notifId);
                });
            });
        },

        markRead(id) {
            fetch("{{ route('notifications.mark') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ notification_id: id })
            }).then(() => {
                location.reload();
            });
        }
    }));
});
</script>
@endpush
