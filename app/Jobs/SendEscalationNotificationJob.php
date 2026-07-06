<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Events\NotificationDelivered;
use App\Events\NotificationDeliveryFailed;
use App\Models\EscalationNotification;
use App\Models\EscalationNotificationAttempt;
use App\Services\Notification\NotificationManager;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendEscalationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $notificationId,
    ) {
        $this->connection = $this->viaConnection();
        $this->queue = $this->viaQueue();
    }

    /**
     * Get the queue connection for the job.
     */
    public function viaConnection(): string
    {
        return config('escalation.queue_connection', 'database');
    }

    /**
     * Get the queue name for the job.
     */
    public function viaQueue(): string
    {
        return config('escalation.queue_name', 'escalations');
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public function tries(): int
    {
        return config('escalation.max_attempts', 3);
    }

    /**
     * Determine the backoff delay times for retrying the job.
     */
    public function backoff(): array
    {
        return config('escalation.backoff', [5, 15, 30]);
    }

    /**
     * Determine the time at which the job should timeout/expire.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the tags that should be assigned to the job for Horizon.
     */
    public function tags(): array
    {
        return [
            'ticket:'.$this->notificationId,
            'notification:'.$this->notificationId,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationManager $manager): void
    {
        [$notification, $attempt] = DB::transaction(function () {
            $notification = EscalationNotification::with(['ticket', 'ticket.agent'])
                ->where('id', $this->notificationId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($notification->status === NotificationStatus::Sent) {
                return [null, null];
            }

            $attemptNumber = $this->attempts();

            $attempt = $notification->attempts()->firstOrCreate(
                [
                    'attempt_number' => $attemptNumber,
                ],
                [
                    'status' => NotificationStatus::Pending,
                    'executed_at' => now(),
                ]
            );

            return [$notification, $attempt];
        });

        if (! $notification || ! $attempt || ! $attempt->wasRecentlyCreated) {
            return;
        }

        $startTime = microtime(true);
        $attemptNumber = $this->attempts();

        try {
            $channelService = $manager->channel($notification->channel);

            if (empty($notification->recipient)) {
                $recipient = $channelService->resolveRecipient($notification->ticket);
                DB::transaction(function () use ($notification, $recipient) {
                    $lockedNotification = EscalationNotification::where('id', $notification->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $lockedNotification->fill(['recipient' => $recipient])->save();
                });
                $notification->refresh();
            }

            $channelService->send($notification);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            DB::transaction(function () use ($notification, $attempt) {
                $lockedAttempt = EscalationNotificationAttempt::where('id', $attempt->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedNotification = EscalationNotification::where('id', $notification->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedAttempt->fill(['status' => NotificationStatus::Sent])->save();
                $lockedNotification->fill(['status' => NotificationStatus::Sent])->save();
            });

            event(new NotificationDelivered(
                $notification->id,
                $notification->ticket_id,
                $notification->channel,
                $duration,
                $attemptNumber
            ));
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            DB::transaction(function () use ($attempt, $e) {
                $lockedAttempt = EscalationNotificationAttempt::where('id', $attempt->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedAttempt->fill([
                    'status' => NotificationStatus::Failed,
                    'error_message' => Str::limit($e->getMessage(), 1000),
                ])->save();
            });

            if ($attemptNumber >= $this->tries()) {
                DB::transaction(function () use ($notification) {
                    $lockedNotification = EscalationNotification::where('id', $notification->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $lockedNotification->fill(['status' => NotificationStatus::Failed])->save();
                });

                event(new NotificationDeliveryFailed(
                    $notification->id,
                    $notification->ticket_id,
                    $notification->channel,
                    $duration,
                    $attemptNumber
                ));
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $notification = EscalationNotification::find($this->notificationId);

        Log::error('Escalation notification failed.', [
            'notification_id' => $this->notificationId,
            'ticket_id' => $notification?->ticket_id,
            'channel' => $notification?->channel?->value,
            'current_attempt' => $this->attempts(),
            'queue' => $this->viaQueue(),
            'exception_message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($notification) {
            $wasAlreadyFailed = false;

            DB::transaction(function () use ($notification, &$wasAlreadyFailed) {
                $lockedNotification = EscalationNotification::where('id', $notification->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $wasAlreadyFailed = $lockedNotification->status === NotificationStatus::Failed;

                if ($lockedNotification->status !== NotificationStatus::Sent) {
                    $lockedNotification->fill(['status' => NotificationStatus::Failed])->save();
                }
            });

            if (! $wasAlreadyFailed) {
                event(new NotificationDeliveryFailed(
                    $notification->id,
                    $notification->ticket_id,
                    $notification->channel,
                    0.0,
                    $this->attempts()
                ));
            }
        }
    }
}
