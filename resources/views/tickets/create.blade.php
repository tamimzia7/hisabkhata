@extends('layouts.app')

@section('title', __('tickets.create.title'))

@section('content')
    <x-tickets.client-shell :back="route('tickets.index')" max="max-w-3xl">
        <div class="glass-strong rounded-2xl p-5 sm:p-6" x-data="{ files: [] }">
            <div class="mb-5">
                <h1 class="text-xl font-bold font-display font-bn text-white">{{ __('tickets.create.title') }}</h1>
                <p class="mt-1 text-sm text-gray-400 font-bn">{{ __('tickets.create.subtitle') }}</p>
            </div>

            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300 font-bn">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label for="ticket-subject" class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('tickets.create.subject') }}</label>
                    <input id="ticket-subject" type="text" name="subject" value="{{ old('subject') }}" required maxlength="180"
                           placeholder="{{ __('tickets.create.subject_placeholder') }}"
                           class="w-full rounded-xl border px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/60 @error('subject') border-red-500/60 @else border-white/10 @enderror bg-black/30 font-bn">
                    @error('subject')
                        <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ticket-category" class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('tickets.create.category') }}</label>
                    <select id="ticket-category" name="category" required
                            class="w-full rounded-xl border px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-500/60 @error('category') border-red-500/60 @else border-white/10 @enderror bg-black/30 font-bn">
                        <option value="">{{ __('tickets.create.category_placeholder') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ __('tickets.categories.'.$category) }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('tickets.create.priority') }}</span>
                    <p class="mb-2 text-xs text-gray-500 font-bn">{{ __('tickets.create.priority_note') }}</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach($priorities as $priority)
                            <label class="cursor-pointer">
                                <input type="radio" name="priority" value="{{ $priority }}" class="peer sr-only"
                                       @checked(old('priority', \App\Models\Ticket::PRIORITY_MEDIUM) === $priority)>
                                <span class="block rounded-xl border border-white/10 bg-black/20 px-3 py-2.5 text-center text-sm font-bold text-gray-400 transition peer-checked:border-emerald-400/60 peer-checked:bg-emerald-500/15 peer-checked:text-emerald-300 font-bn">
                                    {{ __('tickets.priorities.'.$priority) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('priority')
                        <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ticket-description" class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('tickets.create.description') }}</label>
                    <textarea id="ticket-description" name="description" rows="7" required maxlength="5000"
                              placeholder="{{ __('tickets.create.description_placeholder') }}"
                              class="w-full rounded-xl border px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/60 @error('description') border-red-500/60 @else border-white/10 @enderror bg-black/30 font-bn">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('tickets.create.attachments') }}</span>
                    <p class="mb-2 text-xs text-gray-500 font-bn">{{ __('tickets.create.attachment_hint') }}</p>

                    <input id="ticket-attachments" type="file" name="attachments[]" multiple class="sr-only"
                           accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx"
                           @change="files = Array.from($event.target.files || [])">

                    <label for="ticket-attachments"
                           class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-emerald-500/30 bg-emerald-500/5 px-4 py-2.5 text-sm font-bold text-emerald-300 hover:bg-emerald-500/10 transition font-bn">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('tickets.create.choose_files') }}
                    </label>

                    <p x-show="files.length === 0" class="mt-2 text-xs text-gray-500 font-bn">{{ __('tickets.create.no_files_selected') }}</p>

                    <ul x-show="files.length > 0" x-cloak class="mt-2 space-y-1">
                        <template x-for="file in files" :key="file.name + file.size">
                            <li class="flex items-center gap-2 text-xs text-gray-300 font-bn">
                                <svg class="h-3.5 w-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="truncate" x-text="file.name"></span>
                                <span class="text-gray-500 shrink-0" x-text="(file.size / 1048576).toFixed(1) + ' MB'"></span>
                            </li>
                        </template>
                    </ul>

                    @error('attachments')
                        <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
                    @enderror
                    @error('attachments.*')
                        <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('tickets.index') }}"
                       class="rounded-xl border border-white/10 px-4 py-2.5 text-center text-sm font-bold text-gray-300 hover:bg-white/5 transition font-bn">
                        {{ __('tickets.create.cancel') }}
                    </a>
                    <button type="submit" class="btn-green btn-shine rounded-xl px-5 py-2.5 text-sm font-bold font-bn">
                        {{ __('tickets.create.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </x-tickets.client-shell>
@endsection
