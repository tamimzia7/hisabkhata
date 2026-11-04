@if($canReply)
    <div class="border-t border-white/5 bg-white/[0.02] px-3 py-3 sm:px-4"
         x-data="ticketComposer({
             fetchUrl: @js(route('tickets.messages.fetch', $ticket)),
             csrf: @js(csrf_token()),
             lastId: @js((int) ($ticket->messages->last()?->getKey() ?? 0)),
             emptyError: @js(__('tickets.chat.empty_error')),
             sendFailed: @js(__('tickets.chat.send_failed')),
             tooMany: @js(__('tickets.attachments.too_many')),
         })"
         x-init="init()">
        <form x-ref="form" method="POST" action="{{ route('tickets.messages.store', $ticket) }}" enctype="multipart/form-data"
              @submit.prevent="send($event)" class="space-y-2">
            @csrf

            <div x-show="items.length > 0" x-cloak class="flex flex-wrap gap-2">
                <template x-for="(item, index) in items" :key="item.key">
                    <div class="relative rounded-xl border border-white/10 bg-black/25 p-1">
                        <template x-if="item.previewUrl">
                            <img :src="item.previewUrl" :alt="item.name" class="h-16 w-16 rounded-lg object-cover">
                        </template>

                        <template x-if="! item.previewUrl">
                            <div class="flex h-16 w-36 items-center gap-2 px-1.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[8px] font-bold tracking-tight"
                                      :class="item.badgeClass" x-text="item.extension"></span>
                                <span class="min-w-0">
                                    <span class="block truncate text-[11px] text-gray-200 font-bn" x-text="item.name"></span>
                                    <span class="block text-[9px] text-gray-500" x-text="item.sizeLabel"></span>
                                </span>
                            </div>
                        </template>

                        <button type="button" @click="removeItem(index)"
                                title="{{ __('tickets.attachments.remove') }}"
                                aria-label="{{ __('tickets.attachments.remove') }}"
                                class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[11px] font-bold leading-none text-white shadow transition hover:bg-red-400">
                            ×
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex items-end gap-2">
                <label class="glass flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl text-gray-300 transition hover:text-emerald-300"
                       title="{{ __('tickets.chat.attach') }}">
                    <input type="file" name="attachments[]" multiple class="sr-only" x-ref="files"
                           accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" @change="onFiles($event)">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </label>

                <textarea x-ref="input" name="body" rows="1" maxlength="5000"
                          placeholder="{{ __('tickets.chat.reply_placeholder') }}"
                          class="max-h-40 min-h-[40px] flex-1 resize-none rounded-xl border border-white/10 bg-black/30 px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn"
                          @keydown.enter="handleEnter($event)"
                          @input="autoGrow($event.target)"></textarea>

                <button type="submit" :disabled="sending"
                        class="btn-green flex h-10 shrink-0 items-center gap-1.5 rounded-xl px-4 text-sm font-bold font-bn disabled:cursor-not-allowed disabled:opacity-60">
                    <svg x-show="! sending" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span x-text="sending ? '…' : @js(__('tickets.chat.send'))">{{ __('tickets.chat.send') }}</span>
                </button>
            </div>

            <div x-show="sending && progress > 0" x-cloak class="flex items-center gap-2">
                <span class="shrink-0 text-[10px] text-emerald-300 font-bn">{{ __('tickets.attachments.uploading') }}</span>
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-emerald-400 transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                </div>
                <span class="shrink-0 text-[10px] font-bold text-emerald-300 font-bn" x-text="progress + '%'"></span>
            </div>

            <p x-show="error" x-text="error" class="text-xs text-red-400 font-bn" x-cloak></p>
        </form>
    </div>

    @once
        <script>
            function ticketComposer(config) {
                return {
                    sending: false,
                    error: '',
                    items: [],
                    progress: 0,
                    keySeed: 0,
                    lastId: config.lastId,
                    timer: null,

                    init() {
                        this.scrollToBottom(false);

                        this.timer = setInterval(() => this.poll(), 12000);
                    },

                    destroy() {
                        if (this.timer) {
                            clearInterval(this.timer);
                        }

                        this.releasePreviews();
                    },

                    stream() {
                        return document.getElementById('ticket-messages');
                    },

                    scrollToBottom(smooth = true) {
                        this.$nextTick(() => {
                            const stream = this.stream();

                            if (stream) {
                                stream.scrollTo({ top: stream.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                            }
                        });
                    },

                    autoGrow(element) {
                        element.style.height = 'auto';
                        element.style.height = Math.min(element.scrollHeight, 160) + 'px';
                    },

                    handleEnter(event) {
                        if (event.shiftKey || event.isComposing) {
                            return;
                        }

                        event.preventDefault();
                        this.send(event);
                    },

                    onFiles(event) {
                        this.addFiles(Array.from(event.target.files || []));
                        event.target.value = '';
                    },

                    addFiles(files) {
                        const remaining = 5 - this.items.length;

                        if (remaining <= 0) {
                            this.error = config.tooMany;

                            return;
                        }

                        if (files.length > remaining) {
                            this.error = config.tooMany;
                        }

                        files.slice(0, remaining).forEach((file) => {
                            const extension = (file.name.split('.').pop() || '').toUpperCase().slice(0, 5);
                            const isImage = (file.type || '').startsWith('image/');

                            this.items.push({
                                key: ++this.keySeed,
                                file,
                                name: file.name,
                                sizeLabel: this.formatSize(file.size),
                                extension: extension || 'FILE',
                                previewUrl: isImage ? URL.createObjectURL(file) : null,
                                badgeClass: isImage
                                    ? ''
                                    : (extension === 'PDF'
                                        ? 'bg-red-500/15 text-red-400'
                                        : (extension.startsWith('DOC') ? 'bg-blue-500/15 text-blue-400' : 'bg-gray-500/15 text-gray-300')),
                            });
                        });

                        if (files.length <= remaining) {
                            this.error = '';
                        }
                    },

                    removeItem(index) {
                        const item = this.items[index];

                        if (item && item.previewUrl) {
                            URL.revokeObjectURL(item.previewUrl);
                        }

                        this.items.splice(index, 1);
                    },

                    releasePreviews() {
                        this.items.forEach((item) => {
                            if (item.previewUrl) {
                                URL.revokeObjectURL(item.previewUrl);
                            }
                        });
                    },

                    formatSize(bytes) {
                        if (bytes >= 1048576) {
                            return (bytes / 1048576).toFixed(1) + ' MB';
                        }

                        if (bytes >= 1024) {
                            return Math.round(bytes / 1024) + ' KB';
                        }

                        return bytes + ' B';
                    },

                    send() {
                        if (this.sending) {
                            return;
                        }

                        const input = this.$refs.input;
                        const body = (input?.value || '').trim();

                        if (body === '' && this.items.length === 0) {
                            this.error = config.emptyError;

                            return;
                        }

                        this.error = '';
                        this.sending = true;
                        this.progress = 0;

                        const data = new FormData();
                        data.append('body', input.value);
                        this.items.forEach((item) => data.append('attachments[]', item.file));

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', this.$refs.form.action);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.setRequestHeader('X-CSRF-TOKEN', config.csrf);

                        xhr.upload.addEventListener('progress', (event) => {
                            if (event.lengthComputable) {
                                this.progress = Math.max(1, Math.round((event.loaded / event.total) * 100));
                            }
                        });

                        xhr.addEventListener('load', () => {
                            this.sending = false;

                            let payload = {};

                            try {
                                payload = JSON.parse(xhr.responseText || '{}');
                            } catch (exception) {
                                payload = {};
                            }

                            if (xhr.status < 200 || xhr.status >= 300) {
                                const firstError = Object.values(payload.errors || {}).flat().find(Boolean);

                                this.error = firstError || payload.message || config.sendFailed;
                                this.progress = 0;

                                return;
                            }

                            this.onSent(payload);
                        });

                        xhr.addEventListener('error', () => {
                            this.sending = false;
                            this.progress = 0;
                            this.error = config.sendFailed;
                        });

                        xhr.send(data);
                    },

                    onSent(payload) {
                        const stream = this.stream();
                        const input = this.$refs.input;

                        if (stream && payload.html) {
                            stream.insertAdjacentHTML('beforeend', payload.html);
                        }

                        if (payload.last_id) {
                            this.lastId = payload.last_id;
                        }

                        if (input) {
                            input.value = '';
                            input.style.height = 'auto';
                        }

                        this.releasePreviews();
                        this.items = [];
                        this.progress = 0;

                        if (this.$refs.files) {
                            this.$refs.files.value = '';
                        }

                        stream?.querySelector('[data-empty-conversation]')?.remove();

                        this.scrollToBottom();
                    },

                    poll() {
                        fetch(config.fetchUrl + '?after=' + this.lastId, {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        })
                            .then((response) => response.ok ? response.json() : Promise.reject())
                            .then((payload) => {
                                if (! payload.last_id || payload.last_id <= this.lastId) {
                                    return;
                                }

                                const stream = this.stream();

                                if (stream && payload.html) {
                                    stream.insertAdjacentHTML('beforeend', payload.html);
                                    stream.querySelector('[data-empty-conversation]')?.remove();
                                }

                                this.lastId = payload.last_id;
                                this.scrollToBottom();
                            })
                            .catch(() => {});
                    },
                };
            }
        </script>
    @endonce
@else
    <div class="border-t border-white/5 bg-white/[0.02] px-4 py-4 text-center">
        <p class="text-sm text-gray-400 font-bn">
            {{ $ticket->isClosed() ? __('tickets.chat.closed_notice') : __('tickets.chat.closed_button_hint') }}
        </p>
    </div>
@endif
