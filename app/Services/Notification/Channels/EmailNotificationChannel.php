<?php

namespace App\Services\Notification\Channels;

use App\Enums\NotificationChannel;
use App\Exceptions\InvalidRecipientException;
use App\Mail\EscalationNotificationMail;
use App\Models\EscalationNotification;
use App\Models\Ticket;
use App\Services\Notification\NotificationChannelInterface;
use Illuminate\Support\Facades\Mail;

class EmailNotificationChannel implements NotificationChannelInterface
{
    /**
     * Send the email escalation notification.
     */
    public function send(EscalationNotification $notification): void
    {
        if (empty($notification->recipient)) {
            throw new InvalidRecipientException('Email recipient address is empty.');
        }

        Mail::to($notification->recipient)
            ->send(new EscalationNotificationMail($notification));
    }

    /**
     * Resolve the recipient email address for the given ticket.
     */
    public function resolveRecipient(Ticket $ticket): ?string
    {
        return $ticket->agent->email ?? config('escalation.fallback_email');
    }

    /**
     * Get the Enum representation of this channel.
     */
    public function channel(): NotificationChannel
    {
        return NotificationChannel::Email;
    }
}
