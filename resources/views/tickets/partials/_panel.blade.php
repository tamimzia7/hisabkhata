@php
    $initialAttachments = $ticket->attachments->whereNull('message_id');
    $assigneeOptions = $eligibleAssignees->map(fn (\App\Models\User $user): array => [
        'id' => $user->id,
        'name' => $user->name,
        'label' => $user->name.' — '.__('site.admin.role_'.str_replace('-', '_', $user->role)),
    ])->values()->all();
@endphp

<div class="glass rounded-2xl p-4 sm:p-5">
    <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-white font-bn">
        <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ __('tickets.show.ticket_details') }}
    </h2>

    <div class="space-y-3">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.description') }}</p>
            <p class="whitespace-pre-line break-words text-sm text-gray-300 font-bn">{{ $ticket->description }}</p>
        </div>

        @if($initialAttachments->isNotEmpty())
            <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.attachments') }}</p>
                <div class="space-y-1.5">
                    @foreach($initialAttachments as $attachment)
                        <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                           class="flex items-center gap-2 rounded-lg border border-white/10 bg-black/20 px-2.5 py-2 transition hover:bg-black/30">
                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="min-w-0">
                                <span class="block truncate text-xs text-gray-200">{{ $attachment->file_name }}</span>
                                <span class="block text-[10px] text-gray-500">{{ $attachment->humanSize() }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <dl class="grid grid-cols-2 gap-3 border-t border-white/5 pt-3 text-xs">
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.fields.category') }}</dt>
                <dd class="mt-0.5 text-gray-300 font-bn">{{ __('tickets.categories.'.$ticket->category) }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.fields.updated') }}</dt>
                <dd class="mt-0.5 text-gray-300 font-bn">{{ $ticket->updated_at?->format('d M Y, h:i A') }}</dd>
            </div>

            @if($viewer->isSystemUser())
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.created_by') }}</dt>
                    <dd class="mt-0.5">
                        <span class="block font-bold text-gray-200 font-bn">{{ $ticket->user?->name ?? '—' }}</span>
                        <span class="block truncate text-[10px] text-gray-500">{{ $ticket->user?->email }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.assigned_to') }}</dt>
                    <dd class="mt-0.5 text-gray-300 font-bn">
                        {{ $ticket->assignee?->name ?? __('tickets.show.unassigned') }}
                    </dd>
                </div>
            @endif
        </dl>
    </div>
</div>

@if($viewer->isSystemUser())
    <div class="glass rounded-2xl p-4 sm:p-5">
        <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-white font-bn">
            <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ __('tickets.show.management') }}
        </h2>

        <div class="space-y-4">
            @if($allowedStatuses->isNotEmpty())
                <form method="POST" action="{{ route('tickets.status.update', $ticket) }}" class="space-y-2">
                    @csrf
                    <label for="ticket-status" class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.status') }}</label>
                    <select id="ticket-status" name="status"
                            class="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-emerald-500/60 focus:outline-none font-bn">
                        @foreach($allowedStatuses as $status)
                            <option value="{{ $status }}" @selected($ticket->status === $status)>{{ __('tickets.status.'.$status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full rounded-lg bg-emerald-500/15 px-3 py-2 text-xs font-bold text-emerald-400 hover:bg-emerald-500/25 transition font-bn">
                        {{ __('tickets.show.update_status') }}
                    </button>
                </form>
            @endif

            @if($canChangePriority)
                <form method="POST" action="{{ route('tickets.priority.update', $ticket) }}" class="space-y-2 border-t border-white/5 pt-4">
                    @csrf
                    <label for="ticket-priority" class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.priority') }}</label>
                    <select id="ticket-priority" name="priority"
                            class="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-emerald-500/60 focus:outline-none font-bn">
                        @foreach(\App\Models\Ticket::PRIORITIES as $priority)
                            <option value="{{ $priority }}" @selected($ticket->priority === $priority)>{{ __('tickets.priorities.'.$priority) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full rounded-lg bg-emerald-500/15 px-3 py-2 text-xs font-bold text-emerald-400 hover:bg-emerald-500/25 transition font-bn">
                        {{ __('tickets.show.update_priority') }}
                    </button>
                </form>
            @endif

            @if($canAssign)
                <form method="POST" action="{{ route('tickets.assignee.update', $ticket) }}"
                      class="space-y-2 border-t border-white/5 pt-4"
                      x-data="ticketAssignee({
                          selected: @js((string) ($ticket->assignee_id ?? '')),
                          users: @js($assigneeOptions),
                          confirmAssign: @js(__('tickets.show.confirm_assignment')),
                          confirmUnassign: @js(__('tickets.show.confirm_unassign')),
                      })"
                      @submit="confirmSend($event)">
                    @csrf
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.assignment') }}</label>

                    <input type="search" x-model="search" placeholder="{{ __('tickets.show.assign_placeholder') }}"
                           class="w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-xs text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn">

                    <select name="assignee_id" x-model="selected" size="4"
                            class="w-full rounded-lg border border-white/10 bg-black/30 px-2 py-1 text-sm text-white focus:border-emerald-500/60 focus:outline-none font-bn">
                        <option value="">{{ __('tickets.show.unassigned') }}</option>
                        @foreach($eligibleAssignees as $assigneeOption)
                            <option value="{{ $assigneeOption->id }}"
                                    @selected((int) $ticket->assignee_id === (int) $assigneeOption->id)
                                    x-show="matches(@js($assigneeOption->name))">
                                {{ $assigneeOption->name }} — {{ __('site.admin.role_'.str_replace('-', '_', $assigneeOption->role)) }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="w-full rounded-lg bg-emerald-500/15 px-3 py-2 text-xs font-bold text-emerald-400 hover:bg-emerald-500/25 transition font-bn">
                        {{ __('tickets.show.assign_button') }}
                    </button>
                </form>

                @if($ticket->assignments->isNotEmpty())
                    <div class="border-t border-white/5 pt-4">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-bn">{{ __('tickets.show.assignment_history') }}</p>
                        <ul class="space-y-2">
                            @foreach($ticket->assignments->take(6) as $assignment)
                                <li class="rounded-lg bg-black/20 px-2.5 py-2 text-[11px] leading-snug">
                                    <span class="font-bold text-gray-200 font-bn">{{ $assignment->assignee?->name ?? __('tickets.show.unassigned') }}</span>
                                    @if($assignment->assignedBy)
                                        <span class="text-gray-500 font-bn">· {{ $assignment->assignedBy->name }}</span>
                                    @endif
                                    <span class="mt-0.5 block text-[10px] text-gray-500">{{ $assignment->created_at?->format('d M Y, h:i A') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="glass rounded-2xl p-4 sm:p-5">
        <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-white font-bn">
            <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ __('tickets.show.activity_history') }}
        </h2>

        @if($ticket->activities->isEmpty())
            <p class="text-xs text-gray-500 font-bn">{{ __('tickets.show.no_activity') }}</p>
        @else
            <ol class="relative space-y-3 border-l border-white/10 pl-4">
                @foreach($ticket->activities->take(12) as $activity)
                    @php
                        $action = $activity->action;

                        $from = match ($action) {
                            \App\Models\TicketActivity::ACTION_STATUS_CHANGED,
                            \App\Models\TicketActivity::ACTION_REOPENED,
                            \App\Models\TicketActivity::ACTION_REOPEN_REQUESTED => $activity->from_value ? __('tickets.status.'.$activity->from_value) : null,
                            \App\Models\TicketActivity::ACTION_PRIORITY_CHANGED => $activity->from_value ? __('tickets.priorities.'.$activity->from_value) : null,
                            default => $activity->from_value,
                        };

                        $to = match ($action) {
                            \App\Models\TicketActivity::ACTION_STATUS_CHANGED,
                            \App\Models\TicketActivity::ACTION_CREATED => $activity->to_value ? __('tickets.status.'.$activity->to_value) : null,
                            \App\Models\TicketActivity::ACTION_PRIORITY_CHANGED => $activity->to_value ? __('tickets.priorities.'.$activity->to_value) : null,
                            default => $activity->to_value,
                        };
                    @endphp

                    <li class="relative">
                        <span class="absolute -left-[21px] top-1.5 h-2 w-2 rounded-full bg-emerald-400/70"></span>
                        <p class="text-[11px] leading-relaxed text-gray-400 font-bn">
                            <span class="font-bold text-gray-200">{{ $activity->user?->name ?? __('tickets.chat.deleted_sender') }}</span>
                            {{ __('tickets.activity.'.$action, ['from' => $from ?? '—', 'to' => $to ?? '—']) }}
                        </p>
                        <p class="text-[10px] text-gray-600">{{ $activity->created_at?->format('d M Y, h:i A') }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    @once
        <script>
            function ticketAssignee(config) {
                return {
                    search: '',
                    selected: config.selected,
                    users: config.users,

                    matches(name) {
                        const term = this.search.trim().toLowerCase();

                        return term === '' || name.toLowerCase().includes(term);
                    },

                    confirmSend(event) {
                        const user = this.users.find((item) => String(item.id) === String(this.selected));
                        const message = user
                            ? config.confirmAssign.replace(':name', user.name)
                            : config.confirmUnassign;

                        if (! window.confirm(message)) {
                            event.preventDefault();
                        }
                    },
                };
            }
        </script>
    @endonce
@endif
