<?php

use App\Enums\NotificationChannel;

return [
    'default_channels' => [
        NotificationChannel::Email->value,
        NotificationChannel::Slack->value,
    ],
    'queue_connection' => env('ESCALATION_QUEUE_CONNECTION', 'database'),
    'queue_name' => 'escalations',
    'max_attempts' => 3,
    'backoff' => [5, 15, 30], // in seconds
    'fallback_email' => env('ESCALATION_FALLBACK_EMAIL', 'supervisor@example.com'),
];
