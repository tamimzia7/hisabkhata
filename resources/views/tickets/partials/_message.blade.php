@php
    $sender = $message->sender;
    $isFromClient = $sender?->isClient() ?? true;
    $isOwn = $message->sender_id === $viewer->getKey();
    $initial = $sender ? mb_strtoupper(mb_substr($sender->name, 0, 1)) : '?';
    $avatarUrl = $sender?->avatarUrl();
    $hasBody = filled($message->body);
    $hasAttachments = $message->attachments->isNotEmpty();
@endphp

<div class="mb-3 flex gap-2.5 {{ $isFromClient ? 'justify-start' : 'justify-end' }}" data-ticket-message data-id="{{ $message->id }}">
    @if($isFromClient)
        <div class="shrink-0 self-end">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $sender->name }}" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/20 text-xs font-bold text-emerald-300">
                    {{ $initial }}
                </span>
            @endif
        </div>
    @endif

    <div class="max-w-[85%] sm:max-w-[75%]">
        <div class="mb-1 flex items-center gap-2 text-[10px] {{ $isFromClient ? '' : 'justify-end' }}">
            <span class="font-bold text-gray-300 font-bn">
                {{ $isOwn ? __('tickets.chat.you') : ($sender?->name ?? __('tickets.chat.deleted_sender')) }}
            </span>
            @if($sender?->isSystemUser())
                <span class="rounded bg-indigo-500/10 px-1.5 py-0.5 font-bold text-indigo-300 font-bn">{{ __('tickets.chat.team') }}</span>
            @endif
            <span class="text-gray-500">{{ $message->created_at?->format('h:i A') }}</span>
        </div>

        <div class="rounded-2xl border text-sm leading-relaxed font-bn {{ $hasBody || ! $hasAttachments ? 'px-3.5 py-2.5' : 'p-1.5' }} {{ $isFromClient
            ? 'rounded-tl-md border-emerald-500/20 bg-emerald-500/10'
            : 'rounded-tr-md border-indigo-500/20 bg-indigo-500/10' }}">
            @if($hasBody)
                <p class="whitespace-pre-line break-words text-white">{{ $message->body }}</p>
            @endif

            @if($hasAttachments)
                @include('tickets.partials._attachments', [
                    'attachments' => $message->attachments,
                    'ticket' => $ticket,
                    'flush' => ! $hasBody,
                ])
            @endif

            @if($isUnread)
                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-300 font-bn">
                    {{ __('tickets.chat.new_message') }}
                </span>
            @endif
        </div>
    </div>

    @if(! $isFromClient)
        <div class="shrink-0 self-end">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $sender->name }}" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-bold text-indigo-300">
                    {{ $initial }}
                </span>
            @endif
        </div>
    @endif
</div>
