@php
    $sender = $message->sender;
    $isFromClient = $sender?->isClient() ?? true;
    $isOwn = $message->sender_id === $viewer->getKey();
    $initial = $sender ? mb_strtoupper(mb_substr($sender->name, 0, 1)) : '?';
    $avatarUrl = $sender?->avatarUrl();
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

        <div class="rounded-2xl border px-3.5 py-2.5 text-sm leading-relaxed font-bn {{ $isFromClient
            ? 'rounded-tl-md border-emerald-500/20 bg-emerald-500/10'
            : 'rounded-tr-md border-indigo-500/20 bg-indigo-500/10' }}">
            @if(filled($message->body))
                <p class="whitespace-pre-line break-words text-white">{{ $message->body }}</p>
            @endif

            @if($message->attachments->isNotEmpty())
                <div class="mt-2 space-y-1.5">
                    @foreach($message->attachments as $attachment)
                        <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                           class="flex items-center gap-2 rounded-lg bg-black/25 px-2.5 py-1.5 transition hover:bg-black/40">
                            @if($attachment->isImage())
                                <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @else
                                <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @endif
                            <span class="min-w-0">
                                <span class="block truncate text-xs text-gray-100">{{ $attachment->file_name }}</span>
                                <span class="block text-[10px] text-gray-400">{{ $attachment->humanSize() }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
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
