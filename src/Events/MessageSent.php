<?php

namespace PostRun\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an email is accepted by the mail server.
 */
class MessageSent
{
    use Dispatchable, SerializesModels;

    public string $messageId;

    public string $email;

    public ?string $name;

    public string $subject;

    public array $tags;

    public array $metadata;

    public array $rawPayload;

    public function __construct(array $payload)
    {
        $message = $payload['message'] ?? [];

        $this->messageId = $message['id'] ?? '';
        $this->email = $message['to_email'] ?? '';
        $this->name = $message['to_name'] ?? null;
        $this->subject = $message['subject'] ?? '';
        $this->tags = $message['tags'] ?? [];
        $this->metadata = $message['metadata'] ?? [];
        $this->rawPayload = $payload;
    }
}
