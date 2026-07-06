<?php

namespace App\Actions;

use App\Data\EscalateTicketData;
use App\Enums\NotificationStatus;
use App\Enums\TicketStatus;
use App\Events\TicketEscalated;
use App\Exceptions\TicketAlreadyEscalatedException;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class EscalateTicketAction
{
    public function execute(Ticket $ticket, EscalateTicketData $data): Ticket
    {
        $escalatedTicket = DB::transaction(function () use ($ticket, $data) {
            $lockedTicket = Ticket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();

            if ($lockedTicket->status === TicketStatus::Escalated) {
                throw new TicketAlreadyEscalatedException("Ticket ID {$ticket->id} is already escalated.");
            }

            $lockedTicket->update([
                'status' => TicketStatus::Escalated,
                'escalated_at' => now(),
            ]);

            foreach ($data->channels as $channel) {
                $lockedTicket->escalationNotifications()->create([
                    'channel' => $channel,
                    'recipient' => null,
                    'status' => NotificationStatus::Pending,
                ]);
            }

            return $lockedTicket;
        });

        event(new TicketEscalated($escalatedTicket));

        return $escalatedTicket;
    }
}
