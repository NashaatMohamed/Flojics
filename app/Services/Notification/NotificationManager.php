<?php

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use InvalidArgumentException;

class NotificationManager
{
    /** @var array<string, NotificationChannelInterface> */
    protected array $channels = [];

    /**
     * Register a notification channel.
     */
    public function register(NotificationChannelInterface $channel): void
    {
        $this->channels[$channel->channel()->value] = $channel;
    }

    /**
     * Retrieve a registered notification channel by its Enum.
     */
    public function channel(NotificationChannel $channel): NotificationChannelInterface
    {
        return $this->channels[$channel->value] ?? throw new InvalidArgumentException("Notification channel [{$channel->value}] is not registered.");
    }

    /**
     * Check if a notification channel is registered.
     */
    public function has(NotificationChannel $channel): bool
    {
        return isset($this->channels[$channel->value]);
    }

    /**
     * Get all registered notification channels.
     *
     * @return array<string, NotificationChannelInterface>
     */
    public function channels(): array
    {
        return $this->channels;
    }
}
