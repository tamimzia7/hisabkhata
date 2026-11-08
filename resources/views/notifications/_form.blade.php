@php
    $sendableUsers = ($notifiableUsers ?? collect())
        ->map(fn (\App\Models\User $user): array => [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => __('site.admin.role_'.str_replace('-', '_', $user->role ?: 'unknown')),
            'initial' => mb_strtoupper(mb_substr($user->name, 0, 1)),
            'search' => mb_strtolower($user->name.' '.$user->email),
        ])
        ->values()
        ->all();
@endphp

<div class="glass-strong rounded-2xl p-5 sm:p-6"
     x-data="notificationForm({
         users: @js($sendableUsers),
         recipients: @js(old('recipients', 'all')),
         roles: @js(array_values(old('roles', []))),
         selected: @js(array_values(array_map('intval', old('recipient_ids', [])))),
         recipientRequired: @js(__('notifications.send_recipient_required')),
         roleRequired: @js(__('notifications.send_role_required')),
     })">

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300 font-bn">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('notifications.send') }}" class="space-y-5" @submit="validate($event)">
        @csrf

        <div>
            <label for="notification-title" class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('notifications.send_field_title') }}</label>
            <input id="notification-title" type="text" name="title" value="{{ old('title') }}" required maxlength="150"
                   placeholder="{{ __('notifications.send_title_placeholder') }}"
                   class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn">
            @error('title')
                <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="notification-body" class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('notifications.send_field_message') }}</label>
            <textarea id="notification-body" name="body" rows="6" required maxlength="1000"
                      placeholder="{{ __('notifications.send_message_placeholder') }}"
                      class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn">{{ old('body') }}</textarea>
            @error('body')
                <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <span class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('notifications.send_field_recipients') }}</span>
            <div class="grid gap-2 sm:grid-cols-3">
                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-gray-300 transition hover:bg-white/5">
                    <input type="radio" name="recipients" value="all" x-model="recipients"
                           class="border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40">
                    <span class="min-w-0">
                        <span class="block font-bold text-white font-bn">{{ __('notifications.send_recipients_all') }}</span>
                        <span class="block text-[11px] text-gray-500 font-bn">{{ __('notifications.send_recipients_all_hint') }}</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-gray-300 transition hover:bg-white/5">
                    <input type="radio" name="recipients" value="role" x-model="recipients"
                           class="border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40">
                    <span class="min-w-0">
                        <span class="block font-bold text-white font-bn">{{ __('notifications.send_recipients_role') }}</span>
                        <span class="block text-[11px] text-gray-500 font-bn">{{ __('notifications.send_recipients_role_hint') }}</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-gray-300 transition hover:bg-white/5">
                    <input type="radio" name="recipients" value="selected" x-model="recipients"
                           class="border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40">
                    <span class="min-w-0">
                        <span class="block font-bold text-white font-bn">{{ __('notifications.send_recipients_selected') }}</span>
                        <span class="block text-[11px] text-gray-500 font-bn">{{ __('notifications.send_recipients_selected_hint') }}</span>
                    </span>
                </label>
            </div>
            @error('recipients')
                <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="recipients === 'role'" x-cloak class="space-y-2 rounded-xl border border-white/10 bg-black/20 p-3">
            <span class="block text-xs font-bold text-gray-400 font-bn">{{ __('notifications.send_roles_label') }}</span>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach($roleOptions as $option)
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-white/10 bg-black/20 px-3 py-2.5 text-sm text-gray-300 transition hover:bg-white/5">
                        <input type="checkbox" name="roles[]" value="{{ $option['value'] }}" x-model="roles"
                               class="rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40">
                        <span class="text-lg leading-none" aria-hidden="true">{{ $option['icon'] }}</span>
                        <span class="min-w-0 truncate font-bold text-white font-bn">{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles')
                <p class="text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
            @error('roles.*')
                <p class="text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
        </div>

        <div x-show="recipients === 'selected'" x-cloak class="space-y-2 rounded-xl border border-white/10 bg-black/20 p-3">
            <div class="flex items-center justify-between gap-2">
                <input type="search" x-model="search" placeholder="{{ __('notifications.send_search_users') }}"
                       class="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn">
                <span class="shrink-0 rounded-full bg-emerald-500/15 px-2.5 py-1 text-[11px] font-bold text-emerald-400 font-bn">
                    <span x-text="selected.length"></span>
                    <span>/</span>
                    <span x-text="users.length"></span>
                </span>
            </div>

            <div class="admin-scroll max-h-72 space-y-1 overflow-y-auto rounded-lg border border-white/10 bg-black/20 p-2">
                <template x-for="user in filtered" :key="user.id">
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 transition hover:bg-white/5">
                        <input type="checkbox" name="recipient_ids[]" :value="String(user.id)" x-model="selected"
                               class="rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-[10px] font-bold text-emerald-300" x-text="user.initial"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm text-white font-bn" x-text="user.name"></span>
                            <span class="block truncate text-[11px] text-gray-500" x-text="user.email"></span>
                        </span>
                        <span class="shrink-0 rounded bg-white/5 px-2 py-0.5 text-[10px] text-gray-400 font-bn" x-text="user.role"></span>
                    </label>
                </template>

                <p x-show="filtered.length === 0" class="px-2 py-4 text-center text-xs text-gray-500 font-bn">
                    {{ __('notifications.send_no_users') }}
                </p>
            </div>

            @error('recipient_ids')
                <p class="text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
            @error('recipient_ids.*')
                <p class="text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="notification-action-url" class="mb-1.5 block text-sm font-bold text-gray-300 font-bn">{{ __('notifications.send_field_action_url') }}</label>
            <input id="notification-action-url" type="text" name="action_url" value="{{ old('action_url') }}" maxlength="500"
                   placeholder="/dashboard"
                   class="w-full rounded-xl border border-white/10 bg-black/30 px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn">
            <p class="mt-1 text-[11px] text-gray-500 font-bn">{{ __('notifications.send_action_url_hint') }}</p>
            @error('action_url')
                <p class="mt-1 text-xs text-red-400 font-bn">{{ $message }}</p>
            @enderror
        </div>

        <p x-show="error" x-text="error" class="text-xs text-red-400 font-bn" x-cloak></p>

        <div class="flex flex-col-reverse gap-2 border-t border-white/5 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('notifications.index') }}"
               class="rounded-xl border border-white/10 px-5 py-2.5 text-center text-sm font-bold text-gray-300 transition hover:bg-white/5 font-bn">
                {{ __('notifications.cancel') }}
            </a>
            <button type="submit" class="btn-emerald rounded-xl px-5 py-2.5 text-sm font-bold font-bn">
                {{ __('notifications.send_submit') }}
            </button>
        </div>
    </form>
</div>

@once
    <script>
        function notificationForm(config) {
            return {
                recipients: config.recipients || 'all',
                roles: config.roles || [],
                selected: (config.selected || []).map(String),
                search: '',
                error: '',
                users: config.users || [],

                get filtered() {
                    const term = this.search.trim().toLowerCase();

                    if (term === '') {
                        return this.users;
                    }

                    return this.users.filter((user) => user.search.includes(term));
                },

                validate(event) {
                    if (this.recipients === 'selected' && this.selected.length === 0) {
                        event.preventDefault();
                        this.error = config.recipientRequired;

                        return;
                    }

                    if (this.recipients === 'role' && this.roles.length === 0) {
                        event.preventDefault();
                        this.error = config.roleRequired;

                        return;
                    }

                    this.error = '';
                },
            };
        }
    </script>
@endonce
