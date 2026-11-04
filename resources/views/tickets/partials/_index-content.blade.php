@php
    $isSuperAdmin = $viewer->role === \App\Models\User::ROLE_SUPER_ADMIN;
    $isClient = $viewer->isClient();

    $pageTitle = match (true) {
        $isSuperAdmin => __('tickets.all_tickets'),
        $isClient => __('tickets.my_tickets'),
        default => __('tickets.assigned_tickets'),
    };

    $statusFilters = ['' => __('tickets.list.all')]
        + collect(\App\Models\Ticket::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => __('tickets.status.'.$status)])
            ->all();

    $queryBase = array_filter([
        'q' => $search ?: null,
        'priority' => $activePriority ?: null,
        'category' => $activeCategory ?: null,
    ], fn ($value) => filled($value));
@endphp

<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold font-display font-bn text-white">{{ $pageTitle }}</h1>
            <p class="mt-0.5 text-sm text-gray-400 font-bn">
                {{ __('tickets.list.results', ['count' => localize_number($tickets->total())]) }}
            </p>
        </div>

        @if($isClient)
            <a href="{{ route('tickets.create') }}"
               class="btn-green btn-shine inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold font-bn">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('tickets.create_ticket') }}
            </a>
        @endif
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 border-b border-white/5 bg-white/5 px-3 py-3 sm:px-4">
            @foreach($statusFilters as $statusKey => $statusLabel)
                @php
                    $isActive = $activeStatus === $statusKey;
                    $filterQuery = array_filter([
                        ...$queryBase,
                        'status' => $statusKey !== '' ? $statusKey : null,
                    ], fn ($value) => filled($value));
                @endphp

                <a href="{{ route('tickets.index', $filterQuery) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-bold font-bn transition {{ $isActive ? 'bg-emerald-500/20 text-emerald-400' : 'text-gray-400 hover:bg-white/5' }}">
                    {{ $statusLabel }}
                    <span class="ml-1 text-[10px] opacity-70">{{ localize_number($counts[$statusKey !== '' ? $statusKey : 'all'] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-2 border-b border-white/5 px-3 py-3 sm:px-4">
            @if($activeStatus !== '')
                <input type="hidden" name="status" value="{{ $activeStatus }}">
            @endif

            <div class="relative flex-1 min-w-0">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <input type="search" name="q" value="{{ $search }}"
                       placeholder="{{ __('tickets.list.search_placeholder') }}"
                       class="w-full rounded-lg border border-white/10 bg-black/30 py-2 pl-9 pr-3 text-sm text-white placeholder-gray-500 focus:border-emerald-500/60 focus:outline-none font-bn">
            </div>

            <select name="priority" class="rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-emerald-500/60 focus:outline-none font-bn">
                <option value="">{{ __('tickets.list.any_priority') }}</option>
                @foreach(\App\Models\Ticket::PRIORITIES as $priorityOption)
                    <option value="{{ $priorityOption }}" @selected($activePriority === $priorityOption)>{{ __('tickets.priorities.'.$priorityOption) }}</option>
                @endforeach
            </select>

            @if(! $isClient)
                <select name="category" class="rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white focus:border-emerald-500/60 focus:outline-none font-bn">
                    <option value="">{{ __('tickets.list.any_category') }}</option>
                    @foreach(\App\Models\Ticket::CATEGORIES as $categoryOption)
                        <option value="{{ $categoryOption }}" @selected($activeCategory === $categoryOption)>{{ __('tickets.categories.'.$categoryOption) }}</option>
                    @endforeach
                </select>
            @endif

            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-emerald-500/15 px-4 py-2 text-sm font-bold text-emerald-400 hover:bg-emerald-500/25 transition font-bn">
                    {{ __('tickets.list.filter') }}
                </button>
                @if($search !== '' || $activePriority !== '' || $activeCategory !== '' || $activeStatus !== '')
                    <a href="{{ route('tickets.index') }}" class="rounded-lg px-3 py-2 text-sm text-gray-400 hover:text-white transition font-bn">
                        {{ __('tickets.list.reset') }}
                    </a>
                @endif
            </div>
        </form>

        @if($tickets->isEmpty())
            <div class="px-4 py-16 text-center">
                <svg class="mx-auto mb-4 h-12 w-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                @if($activeStatus !== '' || $search !== '' || $activePriority !== '' || $activeCategory !== '')
                    <p class="text-sm text-gray-400 font-bn">{{ __('tickets.list.no_matching') }}</p>
                @elseif($isClient)
                    <p class="font-bold text-white font-bn">{{ __('tickets.list.no_tickets') }}</p>
                    <p class="mt-1 text-sm text-gray-400 font-bn">{{ __('tickets.list.no_tickets_note') }}</p>
                    <a href="{{ route('tickets.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-500/15 px-4 py-2 text-sm font-bold text-emerald-400 hover:bg-emerald-500/25 font-bn">
                        {{ __('tickets.list.empty_cta') }}
                    </a>
                @else
                    <p class="font-bold text-white font-bn">{{ __('tickets.list.no_assigned') }}</p>
                    <p class="mt-1 text-sm text-gray-400 font-bn">{{ __('tickets.list.no_assigned_note') }}</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="bg-black/20 text-left text-[11px] uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-bn">{{ __('tickets.fields.ticket_id') }}</th>
                            <th class="px-4 py-3 font-bn">{{ __('tickets.fields.subject') }}</th>
                            @if(! $isClient)
                                <th class="px-4 py-3 font-bn">{{ __('tickets.fields.client') }}</th>
                            @endif
                            <th class="px-4 py-3 font-bn">{{ __('tickets.fields.priority') }}</th>
                            <th class="px-4 py-3 font-bn">{{ __('tickets.fields.status') }}</th>
                            @if(! $isClient)
                                <th class="px-4 py-3 font-bn">{{ __('tickets.fields.assigned_user') }}</th>
                            @endif
                            <th class="hidden px-4 py-3 font-bn lg:table-cell">{{ __('tickets.fields.last_reply') }}</th>
                            <th class="hidden px-4 py-3 font-bn xl:table-cell">{{ __('tickets.fields.created') }}</th>
                            <th class="px-4 py-3 text-right font-bn">{{ __('tickets.fields.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($tickets as $ticket)
                            @php($hasUnread = in_array($ticket->id, $unreadTicketIds, false))
                            <tr class="hover:bg-white/5 transition {{ $hasUnread ? 'bg-emerald-500/[0.04]' : '' }}">
                                <td class="whitespace-nowrap px-4 py-3 align-top">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-xs text-emerald-400">
                                        @if($hasUnread)
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400" title="{{ __('tickets.unread') }}"></span>
                                        @endif
                                        {{ $ticket->ticket_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="block max-w-xs font-bold text-white hover:text-emerald-300 transition font-bn">
                                        {{ $ticket->subject }}
                                    </a>
                                    <span class="mt-0.5 block text-[11px] text-gray-500 font-bn">{{ __('tickets.categories.'.$ticket->category) }}</span>
                                </td>
                                @if(! $isClient)
                                    <td class="whitespace-nowrap px-4 py-3 align-top">
                                        <span class="text-gray-300 font-bn">{{ $ticket->user?->name ?? '—' }}</span>
                                    </td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 align-top">
                                    <x-tickets.priority-badge :priority="$ticket->priority" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-top">
                                    <x-tickets.status-badge :status="$ticket->status" />
                                </td>
                                @if(! $isClient)
                                    <td class="whitespace-nowrap px-4 py-3 align-top">
                                        @if($ticket->assignee)
                                            <span class="inline-flex items-center gap-2">
                                                @if($ticket->assignee->avatarUrl())
                                                    <img src="{{ $ticket->assignee->avatarUrl() }}" alt="{{ $ticket->assignee->name }}" class="h-6 w-6 rounded-full object-cover">
                                                @else
                                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-[10px] font-bold text-emerald-400">
                                                        {{ mb_strtoupper(mb_substr($ticket->assignee->name, 0, 1)) }}
                                                    </span>
                                                @endif
                                                <span class="text-gray-300 font-bn">{{ $ticket->assignee->name }}</span>
                                            </span>
                                        @else
                                            <span class="text-gray-500 font-bn">{{ __('tickets.show.unassigned') }}</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="hidden whitespace-nowrap px-4 py-3 align-top text-gray-400 lg:table-cell">
                                    {{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="hidden whitespace-nowrap px-4 py-3 align-top text-gray-400 xl:table-cell">
                                    {{ $ticket->created_at?->format('d M Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-400 hover:bg-emerald-500/20 transition font-bn">
                                        {{ __('tickets.list.open') }}
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
                <div class="border-t border-white/5 bg-white/5 px-4 py-3">
                    {{ $tickets->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
