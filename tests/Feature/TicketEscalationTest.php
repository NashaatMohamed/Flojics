<?php

use App\Enums\TicketStatus;
use App\Events\TicketEscalated;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

test('it escalates a ticket successfully and returns 202', function () {
    Event::fake([TicketEscalated::class]);

    $user = User::factory()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $response = $this->actingAs($user)
        ->postJson(route('tickets.escalate', $ticket), [
            'channels' => ['email', 'slack'],
        ]);

    $response->assertStatus(Response::HTTP_ACCEPTED);
    $response->assertJsonPath('data.status', 'escalated');
    $response->assertJsonStructure([
        'data' => [
            'id',
            'subject',
            'priority',
            'status',
            'escalated_at',
            'customer',
            'agent',
            'escalation_notifications',
        ],
    ]);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Escalated);
    expect($ticket->escalated_at)->not->toBeNull();
    expect($ticket->escalationNotifications)->toHaveCount(2);

    Event::assertDispatched(TicketEscalated::class);
});

test('it returns 409 if the ticket is already escalated', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->escalated()->create();

    $response = $this->actingAs($user)
        ->postJson(route('tickets.escalate', $ticket), [
            'channels' => ['email', 'slack'],
        ]);

    $response->assertStatus(Response::HTTP_CONFLICT);
    $response->assertJson([
        'error' => 'ticket_already_escalated',
    ]);
});

test('it returns 422 for validation failure', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('tickets.escalate', $ticket), [
            'channels' => [],
        ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonValidationErrors(['channels']);
});

test('it returns 404 when ticket does not exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/tickets/9999/escalate', [
            'channels' => ['email'],
        ]);

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('it deduplicates channels during escalation', function () {
    Event::fake();

    $user = User::factory()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $response = $this->actingAs($user)
        ->postJson(route('tickets.escalate', $ticket), [
            'channels' => ['email', 'email', 'slack'],
        ]);

    $response->assertStatus(Response::HTTP_ACCEPTED);
    expect($ticket->escalationNotifications()->count())->toBe(2);
});
