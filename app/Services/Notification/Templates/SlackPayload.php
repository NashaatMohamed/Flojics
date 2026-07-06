<?php

namespace App\Services\Notification\Templates;

class SlackPayload
{
    public function __construct(
        protected string $text,
        protected array $blocks,
    ) {}

    /**
     * Get the array representation of the Slack payload.
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'blocks' => $this->blocks,
        ];
    }
}
