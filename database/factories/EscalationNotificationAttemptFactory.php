<?php

namespace Database\Factories;

use App\Enums\NotificationStatus;
use App\Models\EscalationNotification;
use App\Models\EscalationNotificationAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

class EscalationNotificationAttemptFactory extends Factory
{
    protected $model = EscalationNotificationAttempt::class;

    public function definition(): array
    {
        return [
            'escalation_notification_id' => EscalationNotification::factory(),
            'attempt_number' => 1,
            'status' => NotificationStatus::Pending,
            'error_message' => null,
            'executed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Pending,
            'error_message' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Sent,
            'error_message' => null,
        ]);
    }

    public function failed(?string $errorMessage = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Failed,
            'error_message' => $errorMessage ?? 'Channel connection timeout.',
        ]);
    }
}
