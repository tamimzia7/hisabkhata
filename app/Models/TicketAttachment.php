<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'ticket_id', 'message_id', 'user_id', 'disk',
    'file_path', 'file_name', 'mime_type', 'file_size',
])]
class TicketAttachment extends Model
{
    /**
     * The ticket this attachment belongs to.
     *
     * @return BelongsTo<Ticket, self>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * The message this attachment was uploaded with, if any.
     *
     * @return BelongsTo<TicketMessage, self>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'message_id');
    }

    /**
     * The user who uploaded the attachment.
     *
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether the attachment is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * A human readable file size.
     */
    public function humanSize(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Remove the stored file from its disk.
     */
    public function deleteFile(): void
    {
        Storage::disk($this->disk ?: 'local')->delete($this->file_path);
    }
}
