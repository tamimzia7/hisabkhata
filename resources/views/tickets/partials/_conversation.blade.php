@php
    $isClient = $viewer->isClient();
    $canClose = $isClient && $ticket->status !== \App\Models\Ticket::STATUS_CLOSED;
    $canReopen = $isClient && $ticket->isClosed();
@endphp

<div class="space-y-4">
    <div class="glass rounded-2xl p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-400 font-bn">
                    <span class="font-mono text-emerald-400">{{ $ticket->ticket_number }}</span>
                    <span class="text-gray-600">·</span>
                    <span>{{ __('tickets.categories.'.$ticket->category) }}</span>
                    <span class="text-gray-600">·</span>
                    <span>{{ $ticket->created_at?->format('d M Y, h:i A') }}</span>
                </div>
                <h1 class="mt-1 break-words text-lg font-bold text-white font-bn sm:text-xl">{{ $ticket->subject }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <x-tickets.status-badge :status="$ticket->status" />
                    <x-tickets.priority-badge :priority="$ticket->priority" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if($canClose)
                    <form method="POST" action="{{ route('tickets.status.update', $ticket) }}"
                          onsubmit="return confirm(@js(__('tickets.show.close_confirm')))">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\Ticket::STATUS_CLOSED }}">
                        <button type="submit" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-bold text-red-300 hover:bg-red-500/20 transition font-bn">
                            {{ __('tickets.show.close_ticket') }}
                        </button>
                    </form>
                @endif

                @if($canReopen)
                    <form method="POST" action="{{ route('tickets.status.update', $ticket) }}"
                          onsubmit="return confirm(@js(__('tickets.show.reopen_confirm')))">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\Ticket::STATUS_OPEN }}">
                        <button type="submit" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-sm font-bold text-emerald-300 hover:bg-emerald-500/20 transition font-bn">
                            {{ __('tickets.show.request_reopen') }}
                        </button>
                    </form>
                @endif

                @if($viewer->isSystemUser())
                    <a href="{{ route('tickets.index') }}" class="hidden rounded-xl border border-white/10 px-4 py-2 text-sm font-bold text-gray-300 hover:bg-white/5 transition font-bn sm:inline-flex">
                        {{ __('tickets.show.back_to_tickets') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-12">
        <aside class="order-2 space-y-4 lg:order-1 lg:col-span-4">
            @include('tickets.partials._panel')
        </aside>

        <section class="order-1 lg:order-2 lg:col-span-8">
            <div class="glass flex h-[calc(100vh-230px)] min-h-[440px] flex-col overflow-hidden rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-white/5 bg-white/5 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
                        <span class="truncate text-sm font-bold text-white font-bn">{{ __('tickets.chat.team') }}</span>
                        <span class="hidden truncate text-xs text-gray-500 font-bn sm:inline">{{ $ticket->ticket_number }}</span>
                    </div>
                    <span class="shrink-0 text-xs text-gray-400 font-bn">{{ $ticket->last_reply_at?->diffForHumans() }}</span>
                </div>

                @if(count($unreadIds) > 0)
                    <div class="border-b border-emerald-500/20 bg-emerald-500/10 px-4 py-2 text-[11px] font-bold text-emerald-300 font-bn">
                        {{ __('tickets.chat.unread_banner', ['count' => localize_number(count($unreadIds))]) }}
                    </div>
                @endif

                <div id="ticket-messages" class="admin-scroll flex-1 overflow-y-auto px-3 py-4 sm:px-4">
                    @if($ticket->messages->isEmpty())
                        <div class="flex h-full items-center justify-center px-4 text-center" data-empty-conversation>
                            <p class="text-sm text-gray-400 font-bn">{{ __('tickets.chat.empty_conversation') }}</p>
                        </div>
                    @endif

                    @include('tickets.partials._messages', [
                        'ticket' => $ticket,
                        'viewer' => $viewer,
                        'messages' => $ticket->messages,
                        'previous' => null,
                        'unreadIds' => $unreadIds,
                    ])
                </div>

                @include('tickets.partials._composer')
            </div>
        </section>
    </div>
</div>
