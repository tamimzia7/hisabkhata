@php
    $filters = [
        'all' => __('notifications.all'),
        'unread' => __('notifications.unread_filter'),
        'read' => __('notifications.read_filter'),
    ];
@endphp

<div class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold font-bn text-white font-display">{{ __('notifications.title') }}</h1>
            <p class="text-sm text-gray-400 font-bn mt-0.5">
                {{ __('notifications.subtitle', ['unread' => $counts['unread']]) }}
            </p>
        </div>

        @if($counts['unread'] > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="glass px-4 py-2 rounded-lg text-sm font-bn text-white hover:bg-white/5 transition">
                    {{ __('notifications.mark_all_read') }}
                </button>
            </form>
        @endif
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-white/5 bg-white/5 flex flex-wrap items-center gap-2">
            @foreach($filters as $key => $label)
                <a
                    href="{{ route('notifications.index', $key === 'all' ? [] : ['filter' => $key]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-bn font-bold transition {{ $filter === $key ? 'bg-emerald-500/20 text-emerald-400' : 'text-gray-400 hover:bg-white/5' }}"
                >
                    {{ $label }} ({{ $counts[$key] }})
                </a>
            @endforeach
        </div>

        @if($notifications->isEmpty())
            <div class="text-center py-16 px-4">
                <svg class="w-12 h-12 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <p class="text-sm text-gray-400 font-bn">{{ __('notifications.empty') }}</p>
            </div>
        @else
            <ul class="divide-y divide-white/5">
                @foreach($notifications as $notification)
                    @php
                        $title = $notification->data['title'] ?? __('notifications.unknown');
                        $body = $notification->data['body'] ?? '';
                        $actionUrl = $notification->data['action_url'] ?? null;
                    @endphp

                    <li class="p-4 sm:px-6 hover:bg-white/5 transition {{ $notification->read_at ? 'opacity-70' : '' }}">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                            <span class="hidden sm:block w-2 h-2 mt-2 rounded-full {{ $notification->read_at ? 'bg-gray-600' : 'bg-emerald-400' }} shrink-0"></span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if(filled($actionUrl))
                                        <a href="{{ route('notifications.open', $notification->id) }}" class="font-bold text-white hover:text-emerald-300 font-bn">
                                            {{ $title }}
                                        </a>
                                    @else
                                        <p class="font-bold text-white font-bn">{{ $title }}</p>
                                    @endif

                                    <span class="text-[10px] px-2 py-0.5 rounded font-bn {{ $notification->read_at ? 'text-gray-400 bg-white/5' : 'text-emerald-400 bg-emerald-500/10' }}">
                                        {{ $notification->read_at ? __('notifications.read') : __('notifications.unread') }}
                                    </span>
                                </div>

                                @if(filled($body))
                                    <p class="text-sm text-gray-400 mt-1 font-bn">{{ $body }}</p>
                                @endif

                                <p class="text-xs text-gray-500 mt-1 font-bn">
                                    {{ $notification->created_at?->format('d M Y, H:i') }}
                                    @if($notification->created_at)
                                        · {{ $notification->created_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if(! $notification->read_at)
                                    <form method="POST" action="{{ route('notifications.mark') }}">
                                        @csrf
                                        <input type="hidden" name="notification_id" value="{{ $notification->id }}">
                                        <button type="submit" class="text-xs text-emerald-400 hover:text-emerald-300 font-bn px-2 py-1">
                                            {{ __('notifications.mark_read') }}
                                        </button>
                                    </form>
                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('notifications.destroy', $notification->id) }}"
                                    onsubmit="return confirm(@js(__('notifications.confirm_delete')))"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-bn px-2 py-1">
                                        {{ __('notifications.delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if($notifications->hasPages())
                <div class="px-4 sm:px-6 py-3 border-t border-white/5 bg-white/5">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
