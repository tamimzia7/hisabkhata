<?php

namespace App\Models;

use Database\Factories\TicketMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ticket_id', 'sender_id', 'body'])]
class TicketMessage extends Model
{
    /** @use HasFactory<TicketMessageFactory> */
    use HasFactory;

    /**
     * The ticket this message belongs to.
     *
     * @return BelongsTo<Ticket, self>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * The user who wrote the message.
     *
     * @return BelongsTo<User, self>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The files attached to this message.
     *
     * @return HasMany<TicketAttachment, self>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'message_id');
    }

    /**
     * The translated date label shown above a group of messages.
     */
    public function dayLabel(): string
    {
        if ($this->created_at === null) {
            return '';
        }

        if ($this->created_at->isToday()) {
            return __('tickets.chat.today');
        }

        if ($this->created_at->isYesterday()) {
            return __('tickets.chat.yesterday');
        }

        return $this->created_at->translatedFormat('d M Y');
    }
}
