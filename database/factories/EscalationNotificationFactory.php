<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\EscalationNotification;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class EscalationNotificationFactory extends Factory
{
    protected $model = EscalationNotification::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'channel' => $this->faker->randomElement(NotificationChannel::cases()),
            'recipient' => $this->faker->safeEmail(),
            'status' => NotificationStatus::Pending,
        ];
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::Email,
            'recipient' => $this->faker->safeEmail(),
        ]);
    }

    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => NotificationChannel::Slack,
            'recipient' => 'https://hooks.slack.com/services/' . $this->faker->regexify('[A-Za-z0-9]{9}/[A-Za-z0-9]{9}/[A-Za-z0-9]{24}'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Pending,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Sent,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Failed,
        ]);
    }
}
