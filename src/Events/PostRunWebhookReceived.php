<?php

namespace PostRun\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base event dispatched for all PostRun webhooks.
 * Listen to this event if you want to handle all webhook types in one place.
 */
class PostRunWebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $payload,
        public string $eventType
    ) {}

    /**
     * Get the message data from the payload.
     */
    public function getMessage(): array
    {
        return $this->payload['message'] ?? [];
    }

    /**
     * Get the event-specific data from the payload.
     */
    public function getEventData(): array
    {
        return $this->payload['event_data'] ?? [];
    }
}
