<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can escalate the ticket.
     */
    public function escalate(?User $user, Ticket $ticket): bool
    {
        return true;
    }
}
