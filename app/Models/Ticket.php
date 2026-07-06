<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'agent_id', 'subject', 'priority', 'status', 'escalated_at'])]
class Ticket extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'escalated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function escalationNotifications(): HasMany
    {
        return $this->hasMany(EscalationNotification::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Open);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Pending);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Resolved);
    }

    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Escalated);
    }
}
