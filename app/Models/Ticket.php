<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ticket_number', 'user_id', 'assignee_id', 'subject', 'category',
    'priority', 'status', 'description', 'last_reply_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_WAITING_FOR_CLIENT = 'waiting_for_client';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    /**
     * Every ticket status in workflow order.
     *
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_WAITING_FOR_CLIENT,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    /**
     * Statuses that still need attention (not resolved or closed).
     *
     * @var list<string>
     */
    public const UNRESOLVED_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_WAITING_FOR_CLIENT,
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /**
     * Every selectable priority in ascending urgency order.
     *
     * @var list<string>
     */
    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    public const CATEGORY_TECHNICAL_ISSUE = 'technical_issue';

    public const CATEGORY_ACCOUNT_ISSUE = 'account_issue';

    public const CATEGORY_PAYMENT_ISSUE = 'payment_issue';

    public const CATEGORY_BUG_REPORT = 'bug_report';

    public const CATEGORY_FEATURE_REQUEST = 'feature_request';

    public const CATEGORY_OTHER = 'other';

    /**
     * Every selectable ticket category.
     *
     * @var list<string>
     */
    public const CATEGORIES = [
        self::CATEGORY_TECHNICAL_ISSUE,
        self::CATEGORY_ACCOUNT_ISSUE,
        self::CATEGORY_PAYMENT_ISSUE,
        self::CATEGORY_BUG_REPORT,
        self::CATEGORY_FEATURE_REQUEST,
        self::CATEGORY_OTHER,
    ];

    /**
     * The prefix used by every generated ticket number.
     */
    public const NUMBER_PREFIX = 'HK-TKT-';

    /**
     * The client who created the ticket.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The system user currently assigned to the ticket.
     *
     * @return BelongsTo<User, self>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * The conversation messages, oldest first.
     *
     * @return HasMany<TicketMessage, self>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Every attachment uploaded for the ticket or its messages.
     *
     * @return HasMany<TicketAttachment, self>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * The full activity history (status, priority, assignment events).
     *
     * @return HasMany<TicketActivity, self>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest('id');
    }

    /**
     * The assignment history, newest first.
     *
     * @return HasMany<TicketAssignment, self>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class)->latest('id');
    }

    /**
     * Determine whether the ticket is closed for new messages.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Restrict a ticket query to the records the user is allowed to see.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return;
        }

        if ($user->isSystemUser()) {
            $query->where('assignee_id', $user->getKey());

            return;
        }

        $query->where('user_id', $user->getKey());
    }

    /**
     * Restrict a ticket query to tickets holding messages the user has not read.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithUnreadMessagesFor(Builder $query, User $user): void
    {
        $userId = (int) $user->getKey();
        $epoch = '2000-01-01 00:00:00';

        $query->whereHas('messages', function (Builder $messages) use ($userId, $epoch): void {
            $messages
                ->where('sender_id', '!=', $userId)
                ->whereRaw(
                    'ticket_messages.created_at > coalesce((select ticket_reads.last_read_at from ticket_reads where ticket_reads.ticket_id = tickets.id and ticket_reads.user_id = ?), ?)',
                    [$userId, $epoch],
                );
        });
    }

    /**
     * Count the tickets the user can see that hold unread messages.
     */
    public static function unreadTicketCountFor(User $user): int
    {
        return (int) self::query()
            ->visibleTo($user)
            ->withUnreadMessagesFor($user)
            ->count();
    }

    /**
     * Build the ticket number for the given sequence value.
     */
    public static function formatNumber(int|string $sequence): string
    {
        return self::NUMBER_PREFIX.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
        ];
    }
}
