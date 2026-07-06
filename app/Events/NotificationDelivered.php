<?php

namespace App\Events;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

readonly class NotificationDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $notificationId,
        public int $ticketId,
        public NotificationChannel $channel,
        public float $deliveryDuration,
        public int $attemptNumber,
    ) {}
}
