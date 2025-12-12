<?php

namespace PostRun\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an email is rejected before sending.
 */
class MessageRejected
{
    use Dispatchable, SerializesModels;

    public string $messageId;

    public string $email;

    public ?string $name;

    public string $subject;

    public ?string $reason;

    public array $tags;

    public array $metadata;

    public array $rawPayload;

    public function __construct(array $payload)
    {
        $message = $payload['message'] ?? [];
        $eventData = $payload['event_data'] ?? [];

        $this->messageId = $message['id'] ?? '';
        $this->email = $message['to_email'] ?? '';
        $this->name = $message['to_name'] ?? null;
        $this->subject = $message['subject'] ?? '';
        $this->reason = $eventData['reason'] ?? null;
        $this->tags = $message['tags'] ?? [];
        $this->metadata = $message['metadata'] ?? [];
        $this->rawPayload = $payload;
    }
}
