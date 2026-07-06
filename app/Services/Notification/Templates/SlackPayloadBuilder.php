<?php

namespace App\Services\Notification\Templates;

use App\Models\EscalationNotification;

class SlackPayloadBuilder
{
    /**
     * Build the Slack payload for the escalation notification.
     */
    public function build(EscalationNotification $notification): SlackPayload
    {
        return new SlackPayload(
            "Ticket #{$notification->ticket->id} has been escalated.",
            [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🚨 Ticket Escalation Alert',
                        'emoji' => true,
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Ticket ID:*\n#{$notification->ticket->id}",
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Priority:*\n{$notification->ticket->priority->value}",
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Subject:*\n{$notification->ticket->subject}",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Escalated At:*\n{$notification->ticket->escalated_at?->toIso8601String()}",
                    ],
                ],
            ]
        );
    }
}
