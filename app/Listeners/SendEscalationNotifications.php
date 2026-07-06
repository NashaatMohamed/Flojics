<?php

namespace App\Listeners;

use App\Enums\NotificationStatus;
use App\Events\TicketEscalated;
use App\Jobs\SendEscalationNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendEscalationNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function viaQueue(): string
    {
        return config('escalation.queue_name', 'escalations');
    }

    /**
     * Handle the event.
     */
    public function handle(TicketEscalated $event): void
    {
        $ticket = $event->ticket;
        $notifications = $ticket->escalationNotifications()
            ->where('status', NotificationStatus::Pending)
            ->get();

        foreach ($notifications as $notification) {
            SendEscalationNotificationJob::dispatch($notification->id);
        }
    }
}
