<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'escalated_at' => $this->escalated_at?->toIso8601String(),
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'agent' => AgentResource::make($this->whenLoaded('agent')),
            'escalation_notifications' => EscalationNotificationResource::collection($this->whenLoaded('escalationNotifications')),
        ];
    }
}
