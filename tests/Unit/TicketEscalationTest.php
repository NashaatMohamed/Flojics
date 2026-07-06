<?php

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\TicketStatus;
use App\Events\NotificationDelivered;
use App\Events\NotificationDeliveryFailed;
use App\Events\TicketEscalated;
use App\Exceptions\InvalidRecipientException;
use App\Exceptions\NotificationDeliveryException;
use App\Jobs\SendEscalationNotificationJob;
use App\Mail\EscalationNotificationMail;
use App\Models\EscalationNotification;
use App\Models\Ticket;
use App\Services\Notification\NotificationManager;
use App\Services\Notification\Templates\SlackPayload;
use App\Services\Notification\Templates\SlackPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Throwable;

class TicketEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_dispatches_generic_send_job(): void
    {
        Queue::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $action = app(\App\Actions\EscalateTicketAction::class);
        $action->execute($ticket, new \App\Data\EscalateTicketData([NotificationChannel::Email]));

        $event = new TicketEscalated($ticket);
        $listener = app(\App\Listeners\SendEscalationNotifications::class);
        $listener->handle($event);

        $notification = $ticket->escalationNotifications()->first();

        Queue::assertPushed(SendEscalationNotificationJob::class, function ($job) use ($notification) {
            return $job->notificationId === $notification->id;
        });
    }

    public function test_job_resolves_recipient_and_executes_send(): void
    {
        Mail::fake();

        $notification = EscalationNotification::factory()->create([
            'recipient' => null,
            'channel' => NotificationChannel::Email,
        ]);

        $manager = app(NotificationManager::class);
        $job = new SendEscalationNotificationJob($notification->id);
        $job->handle($manager);

        $notification->refresh();
        $this->assertEquals($notification->ticket->agent->email, $notification->recipient);
        $this->assertEquals(NotificationStatus::Sent, $notification->status);

        Mail::assertSent(EscalationNotificationMail::class);
    }

    public function test_job_handles_failure_and_rethrows_exception(): void
    {
        Http::fake([
            '*' => Http::response('error', 500),
        ]);

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'https://slack.com/webhook',
            'channel' => NotificationChannel::Slack,
        ]);

        $manager = app(NotificationManager::class);
        $job = new SendEscalationNotificationJob($notification->id);

        $thrown = false;
        try {
            $job->handle($manager);
        } catch (Throwable $e) {
            $thrown = true;
            $this->assertStringContainsString('Slack webhook failed with status 500', $e->getMessage());
        }

        $this->assertTrue($thrown);

        $notification->refresh();
        $this->assertEquals(NotificationStatus::Pending, $notification->status);

        $attempt = $notification->attempts()->first();
        $this->assertNotNull($attempt);
        $this->assertEquals(NotificationStatus::Failed, $attempt->status);
        $this->assertStringContainsString('Slack webhook failed with status 500', $attempt->error_message);
    }

    public function test_job_idempotency_prevents_duplicate_processing(): void
    {
        Mail::fake();

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'test@example.com',
            'status' => NotificationStatus::Sent,
            'channel' => NotificationChannel::Email,
        ]);

        $manager = app(NotificationManager::class);
        $job = new SendEscalationNotificationJob($notification->id);
        $job->handle($manager);

        $this->assertEquals(0, $notification->attempts()->count());
        Mail::assertNotSent(EscalationNotificationMail::class);
    }

    public function test_failed_callback_updates_notification_status_and_logs(): void
    {
        Log::shouldReceive('error')->once();

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'test@example.com',
            'status' => NotificationStatus::Pending,
            'channel' => NotificationChannel::Email,
        ]);

        $job = new SendEscalationNotificationJob($notification->id);
        $job->failed(new \Exception('Fatal Queue Error'));

        $notification->refresh();
        $this->assertEquals(NotificationStatus::Failed, $notification->status);
    }

    public function test_job_idempotency_prevents_duplicate_processing_on_second_run(): void
    {
        Mail::fake();

        $notification = EscalationNotification::factory()->create([
            'recipient' => null,
            'channel' => NotificationChannel::Email,
        ]);

        $manager = app(NotificationManager::class);
        $job = new SendEscalationNotificationJob($notification->id);

        $job->handle($manager);

        $notification->refresh();
        $this->assertEquals(NotificationStatus::Sent, $notification->status);
        $this->assertEquals(1, $notification->attempts()->count());

        $job->handle($manager);

        $notification->refresh();
        $this->assertEquals(NotificationStatus::Sent, $notification->status);
        $this->assertEquals(1, $notification->attempts()->count());
        Mail::assertSent(EscalationNotificationMail::class, 1);
    }

    public function test_successful_email_sending_and_rendering(): void
    {
        Mail::fake();

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'test@example.com',
            'channel' => NotificationChannel::Email,
        ]);

        $channel = app(\App\Services\Notification\Channels\EmailNotificationChannel::class);
        $channel->send($notification);

        Mail::assertSent(EscalationNotificationMail::class, function ($mail) use ($notification) {
            $this->assertEquals('test@example.com', $mail->to[0]['address']);
            $this->assertEquals("Ticket Escalated: {$notification->ticket->subject}", $mail->envelope()->subject);
            $this->assertEquals('emails.escalation_notification', $mail->content()->markdown);

            $html = $mail->render();
            $this->assertStringContainsString("#{$notification->ticket->id}", $html);
            $this->assertStringContainsString($notification->ticket->subject, $html);

            return true;
        });
    }

    public function test_successful_slack_delivery_and_payload(): void
    {
        Http::fake([
            'https://slack.com/*' => Http::response('ok', 200),
        ]);

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'https://slack.com/webhook',
            'channel' => NotificationChannel::Slack,
        ]);

        $channel = app(\App\Services\Notification\Channels\SlackNotificationChannel::class);
        $channel->send($notification);

        Http::assertSent(function ($request) use ($notification) {
            $this->assertEquals('https://slack.com/webhook', $request->url());
            $payload = $request->data();
            $this->assertStringContainsString("Ticket #{$notification->ticket->id}", $payload['text']);
            $this->assertStringContainsString('Escalation Alert', $payload['blocks'][0]['text']['text']);
            return true;
        });
    }

    public function test_failed_slack_webhook_throws_exception(): void
    {
        Http::fake([
            'https://slack.com/*' => Http::response('error', 500),
        ]);

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'https://slack.com/webhook',
            'channel' => NotificationChannel::Slack,
        ]);

        $channel = app(\App\Services\Notification\Channels\SlackNotificationChannel::class);

        $this->expectException(NotificationDeliveryException::class);
        $this->expectExceptionMessage('Slack webhook failed with status 500');

        $channel->send($notification);
    }

    public function test_slack_payload_dto_serialization(): void
    {
        $notification = EscalationNotification::factory()->create([
            'channel' => NotificationChannel::Slack,
        ]);

        $builder = new SlackPayloadBuilder();
        $payload = $builder->build($notification);

        $this->assertInstanceOf(SlackPayload::class, $payload);

        $array = $payload->toArray();
        $this->assertArrayHasKey('text', $array);
        $this->assertArrayHasKey('blocks', $array);
        $this->assertStringContainsString("Ticket #{$notification->ticket->id}", $array['text']);
    }

    public function test_email_channel_throws_invalid_recipient_exception(): void
    {
        $notification = EscalationNotification::factory()->create([
            'recipient' => null,
            'channel' => NotificationChannel::Email,
        ]);

        $channel = app(\App\Services\Notification\Channels\EmailNotificationChannel::class);

        $this->expectException(InvalidRecipientException::class);
        $this->expectExceptionMessage('Email recipient address is empty.');

        $channel->send($notification);
    }

    public function test_slack_channel_throws_invalid_recipient_exception(): void
    {
        $notification = EscalationNotification::factory()->create([
            'recipient' => null,
            'channel' => NotificationChannel::Slack,
        ]);

        $channel = app(\App\Services\Notification\Channels\SlackNotificationChannel::class);

        $this->expectException(InvalidRecipientException::class);
        $this->expectExceptionMessage('Slack recipient webhook URL is empty.');

        $channel->send($notification);
    }

    public function test_job_dispatches_notification_delivered_event(): void
    {
        Mail::fake();
        Event::fake([NotificationDelivered::class]);

        $notification = EscalationNotification::factory()->create([
            'recipient' => null,
            'channel' => NotificationChannel::Email,
        ]);

        $manager = app(NotificationManager::class);
        $job = new SendEscalationNotificationJob($notification->id);
        $job->handle($manager);

        Event::assertDispatched(NotificationDelivered::class, function ($event) use ($notification) {
            return $event->notificationId === $notification->id
                && $event->ticketId === $notification->ticket_id
                && $event->channel === NotificationChannel::Email
                && $event->attemptNumber === 1
                && $event->deliveryDuration >= 0;
        });
    }

    public function test_job_dispatches_notification_delivery_failed_event_on_exhaustion(): void
    {
        Http::fake([
            '*' => Http::response('error', 500),
        ]);
        Event::fake([NotificationDeliveryFailed::class]);

        $notification = EscalationNotification::factory()->create([
            'recipient' => 'https://slack.com/webhook',
            'channel' => NotificationChannel::Slack,
        ]);

        $manager = app(NotificationManager::class);
        $job = new SendEscalationNotificationJob($notification->id);

        config(['escalation.max_attempts' => 1]);

        try {
            $job->handle($manager);
        } catch (\Throwable $e) {
            // expected to throw
        }

        Event::assertDispatched(NotificationDeliveryFailed::class, function ($event) use ($notification) {
            return $event->notificationId === $notification->id
                && $event->ticketId === $notification->ticket_id
                && $event->channel === NotificationChannel::Slack
                && $event->attemptNumber === 1
                && $event->deliveryDuration >= 0;
        });
    }
}
