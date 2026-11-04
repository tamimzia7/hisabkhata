@php
    $previousDate = isset($previous) && $previous ? $previous->created_at?->toDateString() : null;
    $unreadIds = $unreadIds ?? [];
@endphp

@foreach($messages as $message)
    @php($currentDate = $message->created_at?->toDateString())

    @if($currentDate !== null && $currentDate !== $previousDate)
        <div class="my-4 flex items-center gap-3" data-date-separator>
            <div class="h-px flex-1 bg-white/10"></div>
            <span class="rounded-full border border-white/10 bg-black/20 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 font-bn">
                {{ $message->dayLabel() }}
            </span>
            <div class="h-px flex-1 bg-white/10"></div>
        </div>
        @php($previousDate = $currentDate)
    @endif

    @include('tickets.partials._message', [
        'ticket' => $ticket,
        'viewer' => $viewer,
        'message' => $message,
        'isUnread' => in_array($message->id, $unreadIds, false),
    ])
@endforeach
