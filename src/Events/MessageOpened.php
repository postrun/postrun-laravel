<?php

namespace PostRun\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a recipient opens an email.
 */
class MessageOpened
{
    use Dispatchable, SerializesModels;

    public string $messageId;

    public string $email;

    public ?string $name;

    public string $subject;

    public ?string $userAgent;

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
        $this->userAgent = $eventData['user_agent'] ?? null;
        $this->tags = $message['tags'] ?? [];
        $this->metadata = $message['metadata'] ?? [];
        $this->rawPayload = $payload;
    }
}
