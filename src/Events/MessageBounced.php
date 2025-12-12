<?php

namespace PostRun\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an email bounces.
 */
class MessageBounced
{
    use Dispatchable, SerializesModels;

    public string $messageId;

    public string $email;

    public ?string $name;

    public string $subject;

    public string $bounceType;

    public ?string $bounceSubtype;

    public ?string $diagnosticCode;

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
        $this->bounceType = $eventData['bounce_type'] ?? 'Unknown';
        $this->bounceSubtype = $eventData['bounce_subtype'] ?? null;
        $this->diagnosticCode = $eventData['diagnostic_code'] ?? null;
        $this->tags = $message['tags'] ?? [];
        $this->metadata = $message['metadata'] ?? [];
        $this->rawPayload = $payload;
    }

    /**
     * Check if this is a permanent bounce.
     * Permanent bounces indicate the email address is invalid and should not be retried.
     */
    public function isPermanent(): bool
    {
        return $this->bounceType === 'Permanent';
    }

    /**
     * Check if this is a temporary bounce.
     * Temporary bounces may succeed on retry.
     */
    public function isTemporary(): bool
    {
        return $this->bounceType === 'Transient';
    }
}
