@php
    $attachments = $attachments ?? collect();
    $compact = $compact ?? false;
    $flush = $flush ?? false;

    $images = $attachments->filter(fn ($attachment) => $attachment->isImage())->values();
    $documents = $attachments->reject(fn ($attachment) => $attachment->isImage())->values();
    $singleImage = $images->count() === 1 ? $images->first() : null;
@endphp

@if($attachments->isNotEmpty())
    <div class="{{ $flush ? 'space-y-1.5' : 'mt-2 space-y-2' }}">
        @if($images->isNotEmpty())
            @if($singleImage !== null)
                <figure class="max-w-full">
                    <button type="button"
                            class="group relative block max-w-full cursor-zoom-in overflow-hidden rounded-xl border border-white/10 bg-black/20"
                            title="{{ __('tickets.attachments.view') }}"
                            aria-label="{{ __('tickets.attachments.view') }}"
                            @click="lightbox = {
                                url: @js(route('tickets.attachments.preview', [$ticket, $singleImage])),
                                name: @js($singleImage->file_name),
                                download: @js(route('tickets.attachments.download', [$ticket, $singleImage])),
                            }">
                        <img src="{{ route('tickets.attachments.preview', [$ticket, $singleImage]) }}"
                             alt="{{ $singleImage->file_name }}" loading="lazy"
                             class="w-auto max-w-full object-contain {{ $compact ? 'max-h-40' : 'max-h-72 sm:max-h-80' }}">

                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition duration-200 group-hover:bg-black/20 group-hover:opacity-100">
                            <span class="rounded-full bg-black/60 p-2 text-white shadow-lg">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            </span>
                        </span>
                    </button>

                    @unless($compact)
                        <figcaption class="mt-1 flex items-center justify-between gap-2 text-[10px] text-gray-400">
                            <span class="truncate font-bn">{{ $singleImage->file_name }}</span>
                            <span class="shrink-0">{{ $singleImage->humanSize() }}</span>
                        </figcaption>
                    @endunless
                </figure>
            @else
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach($images as $image)
                        <button type="button"
                                class="group relative aspect-[4/3] w-full cursor-zoom-in overflow-hidden rounded-lg border border-white/10 bg-black/20"
                                title="{{ __('tickets.attachments.view') }}"
                                aria-label="{{ __('tickets.attachments.view') }}"
                                @click="lightbox = {
                                    url: @js(route('tickets.attachments.preview', [$ticket, $image])),
                                    name: @js($image->file_name),
                                    download: @js(route('tickets.attachments.download', [$ticket, $image])),
                                }">
                            <img src="{{ route('tickets.attachments.preview', [$ticket, $image]) }}"
                                 alt="{{ $image->file_name }}" loading="lazy"
                                 class="h-full w-full object-contain">

                            <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition duration-200 group-hover:bg-black/20 group-hover:opacity-100">
                                <span class="rounded-full bg-black/60 p-1.5 text-white shadow-lg">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        @if($documents->isNotEmpty())
            <div class="space-y-1.5">
                @foreach($documents as $document)
                    @php
                        $extension = $document->extension();
                        $extensionLabel = $extension !== '' ? strtoupper($extension) : 'FILE';
                        $iconClasses = match (true) {
                            $document->isPdf() => 'bg-red-500/15 text-red-400',
                            $document->isWordDocument() => 'bg-blue-500/15 text-blue-400',
                            default => 'bg-gray-500/15 text-gray-300',
                        };
                    @endphp

                    <a href="{{ route('tickets.attachments.download', [$ticket, $document]) }}"
                       class="group flex items-center gap-2.5 rounded-xl border border-white/10 bg-black/25 px-2.5 py-2 transition hover:bg-black/40"
                       title="{{ __('tickets.attachments.download') }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-[9px] font-bold tracking-tight {{ $iconClasses }}">
                            {{ $extensionLabel }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-bold text-gray-100 font-bn">{{ $document->file_name }}</span>
                            <span class="mt-0.5 block text-[10px] text-gray-400">{{ $extensionLabel }} · {{ $document->humanSize() }}</span>
                        </span>

                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition group-hover:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endif
