@if($canReply)
    <div class="border-t border-white/5 bg-white/[0.02] px-3 py-3 sm:px-4"
         x-data="ticketComposer({
             fetchUrl: @js(route('tickets.messages.fetch', $ticket)),
             csrf: @js(csrf_token()),
             lastId: @js((int) ($ticket->messages->last()?->getKey() ?? 0)),
             emptyError: @js(__('tickets.chat.empty_error')),
             sendFailed: @js(__('tickets.chat.send_failed')),
         })"
         x-init="init()">
        <form x-ref="form" method="POST" action="{{ route('tickets.messages.store', $ticket) }}" enctype="multipart/form-data"
              @submit.prevent="send($event)" class="space-y-2">
            @csrf

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

            <div x-show="files.length > 0" x-cloak class="flex flex-wrap gap-1.5">
                <template x-for="file in files" :key="file.name + file.size">
                    <span class="flex max-w-[220px] items-center gap-1.5 truncate rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] text-emerald-300 font-bn">
                        <span class="truncate" x-text="file.name"></span>
                    </span>
                </template>
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
                    files: [],
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
                        this.files = Array.from(event.target.files || []);

                        if (this.files.length > 0) {
                            this.error = '';
                        }
                    },

                    send() {
                        if (this.sending) {
                            return;
                        }

                        const input = this.$refs.input;
                        const body = (input?.value || '').trim();

                        if (body === '' && this.files.length === 0) {
                            this.error = config.emptyError;

                            return;
                        }

                        this.error = '';
                        this.sending = true;

                        const data = new FormData();
                        data.append('body', input.value);
                        this.files.forEach((file) => data.append('attachments[]', file));

                        fetch(this.$refs.form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            credentials: 'same-origin',
                            body: data,
                        })
                            .then(async (response) => {
                                if (! response.ok) {
                                    const payload = await response.json().catch(() => ({}));
                                    const firstError = Object.values(payload.errors || {}).flat().find(Boolean);

                                    throw new Error(firstError || payload.message || config.sendFailed);
                                }

                                return response.json();
                            })
                            .then((payload) => {
                                const stream = this.stream();

                                if (stream && payload.html) {
                                    stream.insertAdjacentHTML('beforeend', payload.html);
                                }

                                if (payload.last_id) {
                                    this.lastId = payload.last_id;
                                }

                                input.value = '';
                                input.style.height = 'auto';
                                this.files = [];

                                if (this.$refs.files) {
                                    this.$refs.files.value = '';
                                }

                                const emptyState = stream?.querySelector('[data-empty-conversation]');

                                if (emptyState) {
                                    emptyState.remove();
                                }

                                this.scrollToBottom();
                            })
                            .catch((exception) => {
                                this.error = exception.message || config.sendFailed;
                            })
                            .finally(() => {
                                this.sending = false;
                            });
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
