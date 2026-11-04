<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'user_id', 'action', 'from_value', 'to_value'])]
class TicketActivity extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_STATUS_CHANGED = 'status_changed';

    public const ACTION_PRIORITY_CHANGED = 'priority_changed';

    public const ACTION_ASSIGNED = 'assigned';

    public const ACTION_REASSIGNED = 'reassigned';

    public const ACTION_UNASSIGNED = 'unassigned';

    public const ACTION_REOPENED = 'reopened';

    public const ACTION_REOPEN_REQUESTED = 'reopen_requested';

    /**
     * The ticket this activity belongs to.
     *
     * @return BelongsTo<Ticket, self>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * The user who performed the action.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
