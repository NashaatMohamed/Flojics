<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket): Response
    {
        $ticket->loadMissing(['customer', 'agent', 'escalationNotifications']);

        return Inertia::render('TicketEscalate', [
            'ticket' => TicketResource::make($ticket)->resolve(),
        ]);
    }
}
