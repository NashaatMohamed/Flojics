<?php

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Models\EscalationNotification;
use App\Models\Ticket;

interface NotificationChannelInterface
{
    /**
     * Send the escalation notification.
     */
    public function send(EscalationNotification $notification): void;

    /**
     * Resolve the recipient address/coordinate for the given ticket.
     */
    public function resolveRecipient(Ticket $ticket): ?string;

    /**
     * Get the Enum representation of this channel.
     */
    public function channel(): NotificationChannel;
}
