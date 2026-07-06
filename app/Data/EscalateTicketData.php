<?php

namespace App\Data;

use App\Enums\NotificationChannel;
use App\Http\Requests\EscalateTicketRequest;

readonly class EscalateTicketData
{
    /**
     * @param array<NotificationChannel> $channels
     */
    public function __construct(
        public array $channels,
    ) {}

    public static function fromRequest(EscalateTicketRequest $request): self
    {
        $uniqueChannels = array_unique($request->validated('channels'));

        $channels = array_map(
            fn (string $value) => NotificationChannel::from($value),
            $uniqueChannels
        );

        return new self($channels);
    }
}
