<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can browse the ticket index.
     */
    public function viewAny(User $user): bool
    {
        return (bool) $user->getKey();
    }

    /**
     * Determine whether the user can open the ticket conversation.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        if ($user->isSystemUser()) {
            return $ticket->assignee_id === $user->getKey();
        }

        return $ticket->user_id === $user->getKey();
    }

    /**
     * Only client accounts may open a ticket.
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Determine whether the user can write in the conversation.
     */
    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && ! $ticket->isClosed();
    }

    /**
     * Determine whether the user may move the ticket to the given status.
     */
    public function changeStatus(User $user, Ticket $ticket, string $toStatus): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        if (! in_array($toStatus, Ticket::STATUSES, true)) {
            return false;
        }

        if ($user->role === User::ROLE_SUPER_ADMIN) {
            return true;
        }

        if ($user->isClient()) {
            return $ticket->user_id === $user->getKey()
                && (($ticket->status === Ticket::STATUS_CLOSED && $toStatus === Ticket::STATUS_OPEN)
                    || ($ticket->status !== Ticket::STATUS_CLOSED && $toStatus === Ticket::STATUS_CLOSED));
        }

        return $ticket->assignee_id === $user->getKey()
            && in_array($toStatus, [
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_FOR_CLIENT,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
            ], true);
    }

    /**
     * Determine whether the user may (re)assign the ticket.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    /**
     * Determine whether the user may change the ticket priority.
     */
    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN;
    }

    /**
     * Determine whether the user may download ticket attachments.
     */
    public function viewAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
