<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ticket_id', 'channel', 'recipient', 'status'])]
class EscalationNotification extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(EscalationNotificationAttempt::class);
    }

    public function scopeForChannel(Builder $query, NotificationChannel $channel): Builder
    {
        return $query->where('channel', $channel);
    }
}
