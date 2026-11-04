<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketRead;
use App\Models\User;
use App\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    /**
     * The maximum number of attachments allowed per submission.
     */
    private const MAX_ATTACHMENTS = 5;

    /**
     * The maximum size of a single attachment in kilobytes (25 MB).
     */
    private const MAX_ATTACHMENT_KILOBYTES = 25600;

    /**
     * Show the ticket list for the authenticated user.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $viewer = $request->user();

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $priority = (string) $request->query('priority', '');
        $category = (string) $request->query('category', '');

        $query = Ticket::query()
            ->visibleTo($viewer)
            ->with(['user', 'assignee']);

        if ($search !== '') {
            $query->where(function ($tickets) use ($search): void {
                $tickets
                    ->where('ticket_number', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($users) => $users->where('name', 'like', '%'.$search.'%'));
            });
        }

        if (in_array($status, Ticket::STATUSES, true)) {
            $query->where('status', $status);
        }

        if (in_array($priority, Ticket::PRIORITIES, true)) {
            $query->where('priority', $priority);
        }

        if (in_array($category, Ticket::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        $counts = ['all' => Ticket::query()->visibleTo($viewer)->count()];

        foreach (Ticket::STATUSES as $ticketStatus) {
            $counts[$ticketStatus] = Ticket::query()
                ->visibleTo($viewer)
                ->where('status', $ticketStatus)
                ->count();
        }

        $tickets = $query
            ->orderByRaw('coalesce(tickets.last_reply_at, tickets.created_at) desc')
            ->paginate(15)
            ->withQueryString();

        $unreadTicketIds = Ticket::query()
            ->whereIn('id', $tickets->pluck('id'))
            ->withUnreadMessagesFor($viewer)
            ->pluck('id')
            ->all();

        return view('tickets.index', [
            'viewer' => $viewer,
            'tickets' => $tickets,
            'counts' => $counts,
            'unreadTicketIds' => $unreadTicketIds,
            'search' => $search,
            'activeStatus' => in_array($status, Ticket::STATUSES, true) ? $status : '',
            'activePriority' => in_array($priority, Ticket::PRIORITIES, true) ? $priority : '',
            'activeCategory' => in_array($category, Ticket::CATEGORIES, true) ? $category : '',
            'adminPage' => $viewer->isSystemUser() ? 'admin-tickets' : null,
        ]);
    }

    /**
     * Show the ticket creation form.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Ticket::class);

        return view('tickets.create', [
            'viewer' => $request->user(),
            'categories' => Ticket::CATEGORIES,
            'priorities' => Ticket::PRIORITIES,
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Ticket::class);

        $viewer = $request->user();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(Ticket::CATEGORIES)],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'description' => ['required', 'string', 'max:5000'],
            ...$this->attachmentRules(),
        ], $this->validationMessages());

        $ticket = DB::transaction(function () use ($request, $viewer, $validated): Ticket {
            $ticket = $this->createTicketRecord($viewer, $validated);

            $this->storeAttachments($request, $ticket, null, $viewer);
            $this->recordActivity($ticket, $viewer, TicketActivity::ACTION_CREATED, null, $ticket->status);

            return $ticket;
        });

        $this->notifySuperAdmins(
            __('tickets.notifications.created_title', ['number' => $ticket->ticket_number]),
            __('tickets.notifications.created_body', [
                'name' => $viewer->name,
                'subject' => $ticket->subject,
                'priority' => __('tickets.priorities.'.$ticket->priority),
            ]),
            $this->ticketUrl($ticket),
        );

        $this->logAdminActivity($request, 'ticket_created', $ticket->ticket_number);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('toast', __('tickets.messages.ticket_created', ['number' => $ticket->ticket_number]));
    }

    /**
     * Show the Messenger-style ticket conversation.
     */
    public function show(Request $request, Ticket $ticket): View
    {
        $viewer = $request->user();

        abort_unless($viewer->can('view', $ticket), 404);

        $unreadSince = $this->lastReadAt($ticket, $viewer);
        $this->markRead($ticket, $viewer);

        $ticket->load([
            'user',
            'assignee',
            'messages.sender',
            'messages.attachments',
            'activities.user',
            'assignments.assignee',
            'assignments.assignedBy',
        ]);

        $unreadIds = $ticket->messages
            ->filter(fn (TicketMessage $message): bool => $message->created_at > $unreadSince
                && $message->sender_id !== $viewer->getKey())
            ->pluck('id')
            ->all();

        $allowedStatuses = collect(Ticket::STATUSES)
            ->filter(fn (string $status): bool => $viewer->can('changeStatus', [$ticket, $status]))
            ->values();

        return view('tickets.show', [
            'viewer' => $viewer,
            'ticket' => $ticket,
            'unreadIds' => $unreadIds,
            'canReply' => $viewer->can('reply', $ticket),
            'canAssign' => $viewer->can('assign', $ticket),
            'canChangePriority' => $viewer->can('changePriority', $ticket),
            'allowedStatuses' => $allowedStatuses,
            'eligibleAssignees' => $viewer->can('assign', $ticket)
                ? User::query()->whereIn('role', User::SYSTEM_ROLES)->orderBy('name')->get()
                : collect(),
            'adminPage' => $viewer->isSystemUser() ? 'admin-tickets' : null,
        ]);
    }

    /**
     * Store a new message in the ticket conversation.
     */
    public function storeMessage(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('view', $ticket), 404);

        if (! $viewer->can('reply', $ticket)) {
            throw ValidationException::withMessages([
                'body' => __('tickets.messages.ticket_closed'),
            ]);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachments'],
            ...$this->attachmentRules(),
        ], $this->validationMessages());

        $message = DB::transaction(function () use ($request, $viewer, $ticket, $validated): TicketMessage {
            $message = $ticket->messages()->create([
                'sender_id' => $viewer->getKey(),
                'body' => filled($validated['body'] ?? null) ? trim((string) $validated['body']) : null,
            ]);

            $this->storeAttachments($request, $ticket, $message, $viewer);

            $ticket->forceFill(['last_reply_at' => $message->created_at])->save();

            if ($viewer->isClient() && $ticket->status === Ticket::STATUS_RESOLVED) {
                $previousStatus = $ticket->status;
                $ticket->forceFill(['status' => Ticket::STATUS_OPEN])->save();
                $this->recordActivity($ticket, $viewer, TicketActivity::ACTION_REOPENED, $previousStatus, Ticket::STATUS_OPEN);
            }

            $this->markRead($ticket, $viewer);

            return $message;
        });

        $this->notifyAboutReply($ticket, $message);

        if ($request->expectsJson()) {
            $message->load(['sender', 'attachments']);

            $previous = TicketMessage::query()
                ->where('ticket_id', $ticket->getKey())
                ->where('id', '<', $message->getKey())
                ->orderByDesc('id')
                ->first();

            return response()->json([
                'ok' => true,
                'html' => view('tickets.partials._messages', [
                    'ticket' => $ticket,
                    'viewer' => $viewer,
                    'messages' => collect([$message]),
                    'previous' => $previous,
                    'unreadIds' => [],
                ])->render(),
                'last_id' => $message->getKey(),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('toast', __('tickets.messages.reply_sent'));
    }

    /**
     * Return conversation messages newer than the given message id.
     */
    public function fetchMessages(Request $request, Ticket $ticket): JsonResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('view', $ticket), 404);

        $after = max(0, (int) $request->query('after', 0));

        $this->markRead($ticket, $viewer);

        $messages = TicketMessage::query()
            ->where('ticket_id', $ticket->getKey())
            ->where('id', '>', $after)
            ->with(['sender', 'attachments'])
            ->orderBy('id')
            ->get();

        $previous = $after > 0
            ? TicketMessage::query()
                ->where('ticket_id', $ticket->getKey())
                ->where('id', '<=', $after)
                ->orderByDesc('id')
                ->first()
            : null;

        return response()->json([
            'ok' => true,
            'html' => $messages->isEmpty() ? '' : view('tickets.partials._messages', [
                'ticket' => $ticket,
                'viewer' => $viewer,
                'messages' => $messages,
                'previous' => $previous,
                'unreadIds' => [],
            ])->render(),
            'last_id' => $messages->isEmpty() ? $after : $messages->last()->getKey(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Change the ticket status.
     */
    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('view', $ticket), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Ticket::STATUSES)],
        ], $this->validationMessages());

        $toStatus = (string) $validated['status'];

        abort_unless($viewer->can('changeStatus', [$ticket, $toStatus]), 403);

        if ($ticket->status === $toStatus) {
            return back()->with('toast', __('tickets.messages.status_unchanged'));
        }

        $fromStatus = $ticket->status;

        DB::transaction(function () use ($viewer, $ticket, $fromStatus, $toStatus): void {
            $ticket->forceFill(['status' => $toStatus])->save();

            $action = $toStatus === Ticket::STATUS_OPEN
                ? ($viewer->isClient() ? TicketActivity::ACTION_REOPEN_REQUESTED : TicketActivity::ACTION_REOPENED)
                : TicketActivity::ACTION_STATUS_CHANGED;

            $this->recordActivity($ticket, $viewer, $action, $fromStatus, $toStatus);
        });

        $this->notifyAboutStatusChange($ticket, $viewer, $fromStatus, $toStatus);
        $this->logAdminActivity($request, 'ticket_status_changed', $ticket->ticket_number);

        return back()->with('toast', __('tickets.messages.status_updated', [
            'status' => __('tickets.status.'.$toStatus),
        ]));
    }

    /**
     * Change the ticket priority.
     */
    public function updatePriority(Request $request, Ticket $ticket): RedirectResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('view', $ticket), 404);
        abort_unless($viewer->can('changePriority', $ticket), 403);

        $validated = $request->validate([
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
        ], $this->validationMessages());

        $toPriority = (string) $validated['priority'];

        if ($ticket->priority === $toPriority) {
            return back()->with('toast', __('tickets.messages.priority_unchanged'));
        }

        $fromPriority = $ticket->priority;

        DB::transaction(function () use ($viewer, $ticket, $fromPriority, $toPriority): void {
            $ticket->forceFill(['priority' => $toPriority])->save();
            $this->recordActivity($ticket, $viewer, TicketActivity::ACTION_PRIORITY_CHANGED, $fromPriority, $toPriority);
        });

        $recipients = collect([$ticket->user, $ticket->assignee])
            ->filter()
            ->reject(fn (User $user): bool => $user->getKey() === $viewer->getKey())
            ->unique('id');

        $this->notifyUsers(
            $recipients,
            __('tickets.notifications.priority_title', ['number' => $ticket->ticket_number]),
            __('tickets.notifications.priority_body', [
                'subject' => $ticket->subject,
                'priority' => __('tickets.priorities.'.$toPriority),
            ]),
            $this->ticketUrl($ticket),
        );

        $this->logAdminActivity($request, 'ticket_priority_changed', $ticket->ticket_number);

        return back()->with('toast', __('tickets.messages.priority_updated', [
            'priority' => __('tickets.priorities.'.$toPriority),
        ]));
    }

    /**
     * Assign (or reassign) the ticket to an eligible system user.
     */
    public function updateAssignee(Request $request, Ticket $ticket): RedirectResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('view', $ticket), 404);
        abort_unless($viewer->can('assign', $ticket), 403);

        $validated = $request->validate([
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereIn('role', User::SYSTEM_ROLES),
            ],
        ], $this->validationMessages());

        $previousAssignee = $ticket->assignee;
        $newAssignee = filled($validated['assignee_id'] ?? null)
            ? User::query()->findOrFail($validated['assignee_id'])
            : null;

        if ($previousAssignee?->getKey() === $newAssignee?->getKey()) {
            return back()->with('toast', __('tickets.messages.assignment_unchanged'));
        }

        $previousStatus = $ticket->status;

        DB::transaction(function () use ($viewer, $ticket, $newAssignee, $previousAssignee, $previousStatus): void {
            $status = $previousStatus;

            if ($newAssignee === null) {
                if (in_array($status, [Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS], true)) {
                    $status = Ticket::STATUS_OPEN;
                }
            } else {
                if ($status === Ticket::STATUS_OPEN) {
                    $status = Ticket::STATUS_ASSIGNED;
                } elseif (in_array($status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true)) {
                    $status = Ticket::STATUS_IN_PROGRESS;
                }
            }

            $ticket->forceFill([
                'assignee_id' => $newAssignee?->getKey(),
                'status' => $status,
            ])->save();

            $action = match (true) {
                $newAssignee === null => TicketActivity::ACTION_UNASSIGNED,
                $previousAssignee !== null => TicketActivity::ACTION_REASSIGNED,
                default => TicketActivity::ACTION_ASSIGNED,
            };

            $this->recordActivity(
                $ticket,
                $viewer,
                $action,
                $previousAssignee?->name,
                $newAssignee?->name,
            );

            if ($status !== $previousStatus) {
                $this->recordActivity($ticket, $viewer, TicketActivity::ACTION_STATUS_CHANGED, $previousStatus, $status);
            }

            TicketAssignment::create([
                'ticket_id' => $ticket->getKey(),
                'assignee_id' => $newAssignee?->getKey(),
                'assigned_by_id' => $viewer->getKey(),
            ]);
        });

        if ($newAssignee !== null && $newAssignee->getKey() !== $viewer->getKey()) {
            $this->notifyUsers(
                collect([$newAssignee]),
                __('tickets.notifications.assigned_title', ['number' => $ticket->ticket_number]),
                __('tickets.notifications.assigned_body', [
                    'subject' => $ticket->subject,
                    'priority' => __('tickets.priorities.'.$ticket->priority),
                    'by' => $viewer->name,
                ]),
                $this->ticketUrl($ticket),
            );
        }

        if ($previousAssignee !== null && $previousAssignee->getKey() !== $newAssignee?->getKey() && $previousAssignee->getKey() !== $viewer->getKey()) {
            $this->notifyUsers(
                collect([$previousAssignee]),
                __('tickets.notifications.reassigned_title', ['number' => $ticket->ticket_number]),
                __('tickets.notifications.reassigned_body', [
                    'subject' => $ticket->subject,
                    'assignee' => $newAssignee?->name ?? __('tickets.show.unassigned'),
                ]),
                route('tickets.index', absolute: false),
                'warning',
            );
        }

        if ($ticket->user !== null && $ticket->user->getKey() !== $viewer->getKey()) {
            $this->notifyUsers(
                collect([$ticket->user]),
                __('tickets.notifications.owner_assigned_title', ['number' => $ticket->ticket_number]),
                $newAssignee !== null
                    ? __('tickets.notifications.owner_assigned_body', ['assignee' => $newAssignee->name])
                    : __('tickets.notifications.owner_unassigned_body'),
                $this->ticketUrl($ticket),
            );
        }

        $this->logAdminActivity($request, 'ticket_assigned', $ticket->ticket_number);

        return back()->with('toast', __('tickets.messages.assignment_updated'));
    }

    /**
     * Download a private ticket attachment.
     */
    public function downloadAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('viewAttachment', $ticket), 404);
        abort_unless((int) $attachment->ticket_id === (int) $ticket->getKey(), 404);

        $disk = Storage::disk($attachment->disk ?: 'local');

        abort_unless($disk->exists($attachment->file_path), 404);

        return $disk->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Render a private image attachment inline for in-conversation previews.
     */
    public function previewAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $viewer = $request->user();

        abort_unless($viewer->can('viewAttachment', $ticket), 404);
        abort_unless((int) $attachment->ticket_id === (int) $ticket->getKey(), 404);
        abort_unless($attachment->isImage(), 404);

        $disk = Storage::disk($attachment->disk ?: 'local');

        abort_unless($disk->exists($attachment->file_path), 404);

        return $disk->response($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }

    /**
     * Persist a ticket record with a collision-free ticket number.
     *
     * The number is derived from the unique auto-increment key, so concurrent
     * requests can never receive the same ticket number.
     *
     * @param  array<string, mixed>  $validated
     */
    private function createTicketRecord(User $owner, array $validated): Ticket
    {
        $ticket = new Ticket([
            'user_id' => $owner->getKey(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'status' => Ticket::STATUS_OPEN,
        ]);

        $ticket->ticket_number = 'PENDING-'.Str::uuid()->toString();
        $ticket->save();

        $ticket->forceFill([
            'ticket_number' => Ticket::formatNumber($ticket->getKey()),
        ])->save();

        return $ticket;
    }

    /**
     * Store validated attachment uploads for a ticket or message.
     */
    private function storeAttachments(Request $request, Ticket $ticket, ?TicketMessage $message, User $uploader): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('ticket-attachments', 'local');

            if ($path === false) {
                throw ValidationException::withMessages([
                    'attachments' => __('tickets.validation.attachment_store_failed'),
                ]);
            }

            $originalName = basename($file->getClientOriginalName());
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $baseName = pathinfo($originalName, PATHINFO_FILENAME) ?: 'attachment';

            $ticket->attachments()->create([
                'message_id' => $message?->getKey(),
                'user_id' => $uploader->getKey(),
                'disk' => 'local',
                'file_path' => $path,
                'file_name' => Str::limit($baseName, 180).($extension !== '' ? '.'.$extension : ''),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize() ?: 0,
            ]);
        }
    }

    /**
     * The validation rules shared by every attachment upload.
     *
     * @return array<string, list<string>>
     */
    private function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:'.self::MAX_ATTACHMENT_KILOBYTES],
        ];
    }

    /**
     * The translated validation messages for ticket forms.
     *
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'subject.required' => __('tickets.validation.subject_required'),
            'subject.max' => __('tickets.validation.subject_max'),
            'category.required' => __('tickets.validation.category_required'),
            'category.in' => __('tickets.validation.category_invalid'),
            'priority.required' => __('tickets.validation.priority_required'),
            'priority.in' => __('tickets.validation.priority_invalid'),
            'description.required' => __('tickets.validation.description_required'),
            'description.max' => __('tickets.validation.description_max'),
            'body.max' => __('tickets.validation.message_max'),
            'body.required_without' => __('tickets.validation.message_required'),
            'attachments.max' => __('tickets.validation.attachments_max'),
            'attachments.*.file' => __('tickets.validation.attachment_invalid'),
            'attachments.*.mimes' => __('tickets.validation.attachment_type'),
            'attachments.*.max' => __('tickets.validation.attachment_size'),
            'status.required' => __('tickets.validation.status_required'),
            'status.in' => __('tickets.validation.status_invalid'),
            'assignee_id.integer' => __('tickets.validation.assignee_invalid'),
            'assignee_id.exists' => __('tickets.validation.assignee_invalid'),
        ];
    }

    /**
     * Record a ticket activity entry.
     */
    private function recordActivity(Ticket $ticket, User $actor, string $action, ?string $from = null, ?string $to = null): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->getKey(),
            'user_id' => $actor->getKey(),
            'action' => $action,
            'from_value' => $from,
            'to_value' => $to,
        ]);
    }

    /**
     * Track that the user has read the conversation up to now.
     */
    private function markRead(Ticket $ticket, User $user): void
    {
        TicketRead::query()->updateOrCreate(
            ['ticket_id' => $ticket->getKey(), 'user_id' => $user->getKey()],
            ['last_read_at' => now()],
        );
    }

    /**
     * The moment the user last read the conversation.
     */
    private function lastReadAt(Ticket $ticket, User $user): Carbon
    {
        $lastReadAt = TicketRead::query()
            ->where('ticket_id', $ticket->getKey())
            ->where('user_id', $user->getKey())
            ->value('last_read_at');

        return $lastReadAt !== null ? Carbon::parse($lastReadAt) : Carbon::parse('2000-01-01 00:00:00');
    }

    /**
     * Notify the relevant people that a new reply has been posted.
     */
    private function notifyAboutReply(Ticket $ticket, TicketMessage $message): void
    {
        $actor = $message->sender;

        if ($actor === null) {
            return;
        }

        $recipients = $actor->isClient()
            ? collect([$ticket->assignee])->filter()->merge($this->superAdmins())
            : collect([$ticket->user])->filter();

        $excerpt = filled($message->body)
            ? Str::limit((string) $message->body, 120)
            : __('tickets.notifications.attachment_only');

        $this->notifyUsers(
            $recipients,
            __('tickets.notifications.reply_title', ['number' => $ticket->ticket_number]),
            __('tickets.notifications.reply_body', [
                'name' => $actor->name,
                'subject' => $ticket->subject,
                'excerpt' => $excerpt,
            ]),
            $this->ticketUrl($ticket),
        );
    }

    /**
     * Notify the relevant people that the ticket status changed.
     */
    private function notifyAboutStatusChange(Ticket $ticket, User $actor, string $fromStatus, string $toStatus): void
    {
        $titleKey = match ($toStatus) {
            Ticket::STATUS_RESOLVED => 'tickets.notifications.resolved_title',
            Ticket::STATUS_CLOSED => 'tickets.notifications.closed_title',
            Ticket::STATUS_OPEN => 'tickets.notifications.reopened_title',
            default => 'tickets.notifications.status_title',
        };

        $level = match ($toStatus) {
            Ticket::STATUS_RESOLVED => 'success',
            Ticket::STATUS_CLOSED => 'warning',
            default => 'info',
        };

        $recipients = collect([$ticket->user, $ticket->assignee])
            ->filter()
            ->merge($this->superAdmins())
            ->reject(fn (User $user): bool => $user->getKey() === $actor->getKey());

        $this->notifyUsers(
            $recipients,
            __($titleKey, [
                'number' => $ticket->ticket_number,
                'status' => __('tickets.status.'.$toStatus),
            ]),
            __('tickets.notifications.status_body', [
                'subject' => $ticket->subject,
                'from' => __('tickets.status.'.$fromStatus),
                'to' => __('tickets.status.'.$toStatus),
            ]),
            $this->ticketUrl($ticket),
            $level,
        );
    }

    /**
     * Send a notification to the given users without duplicates.
     *
     * @param  Collection<int, User>  $users
     */
    private function notifyUsers(Collection $users, string $title, string $body, string $actionUrl, string $level = 'info'): void
    {
        $recipients = $users->filter()->unique('id')->values();

        if ($recipients->isEmpty()) {
            return;
        }

        app(NotificationService::class)->sendToUsers($recipients, $title, $body, $actionUrl, $level);
    }

    /**
     * Notify every super admin.
     */
    private function notifySuperAdmins(string $title, string $body, string $actionUrl): void
    {
        $this->notifyUsers($this->superAdmins(), $title, $body, $actionUrl);
    }

    /**
     * Every super admin account.
     *
     * @return Collection<int, User>
     */
    private function superAdmins(): Collection
    {
        return User::query()->where('role', User::ROLE_SUPER_ADMIN)->get();
    }

    /**
     * The internal, relative URL of a ticket conversation.
     */
    private function ticketUrl(Ticket $ticket): string
    {
        return route('tickets.show', $ticket, absolute: false);
    }

    /**
     * Log an admin-panel audit entry for the action.
     */
    private function logAdminActivity(Request $request, string $action, string $subject): void
    {
        if (! $request->user()->isSystemUser()) {
            return;
        }

        AdminActivity::create([
            'user_id' => $request->user()->getKey(),
            'action' => $action,
            'subject' => $subject,
            'ip_address' => $request->ip(),
        ]);
    }
}
