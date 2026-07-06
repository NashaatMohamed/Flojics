<?php

namespace App\Services\Notification\Channels;

use App\Enums\NotificationChannel;
use App\Exceptions\InvalidRecipientException;
use App\Exceptions\NotificationDeliveryException;
use App\Models\EscalationNotification;
use App\Models\Ticket;
use App\Services\Notification\NotificationChannelInterface;
use App\Services\Notification\Templates\SlackPayloadBuilder;
use Illuminate\Support\Facades\Http;

class SlackNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        protected SlackPayloadBuilder $payloadBuilder,
    ) {}

    /**
     * Send the Slack webhook escalation notification.
     */
    public function send(EscalationNotification $notification): void
    {
        if (empty($notification->recipient)) {
            throw new InvalidRecipientException('Slack recipient webhook URL is empty.');
        }

        $payload = $this->payloadBuilder->build($notification);

        $response = Http::post($notification->recipient, $payload->toArray());

        if ($response->failed()) {
            throw new NotificationDeliveryException("Slack webhook failed with status {$response->status()}: {$response->body()}");
        }
    }

    /**
     * Resolve the recipient Slack webhook URL.
     */
    public function resolveRecipient(Ticket $ticket): ?string
    {
        return config('services.slack.webhook_url');
    }

    /**
     * Get the Enum representation of this channel.
     */
    public function channel(): NotificationChannel
    {
        return NotificationChannel::Slack;
    }
}
