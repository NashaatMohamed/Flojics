<?php

namespace App\Http\Controllers\Api;

use App\Actions\EscalateTicketAction;
use App\Data\EscalateTicketData;
use App\Http\Controllers\Controller;
use App\Http\Requests\EscalateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

class TicketEscalationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Escalate the specified ticket.
     */
    public function escalate(
        Ticket $ticket,
        EscalateTicketRequest $request,
        EscalateTicketAction $action
    ) {
        $this->authorize('escalate', $ticket);

        $data = EscalateTicketData::fromRequest($request);

        $escalatedTicket = $action->execute($ticket, $data);

        $escalatedTicket->loadMissing(['customer', 'agent', 'escalationNotifications']);

        return (new TicketResource($escalatedTicket))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
