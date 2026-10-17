<?php

namespace PostRun\Laravel\Transport;

use PostRun\Exceptions\PostRunException;
use PostRun\PostRunClient;
use PostRun\Priority;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class PostRunTransport extends AbstractTransport
{
    /**
     * Header carrying comma-separated PostRun tags.
     */
    public const TAGS_HEADER = 'X-PostRun-Tags';

    /**
     * Header carrying JSON-encoded PostRun metadata.
     */
    public const METADATA_HEADER = 'X-PostRun-Meta';

    /**
     * Header carrying the PostRun sending priority: high, normal or low.
     */
    public const PRIORITY_HEADER = 'X-PostRun-Priority';

    /**
     * Standard headers sent along to PostRun, which passes them on to the
     * email provider. Custom X-* headers are sent along as well.
     */
    public const FORWARDED_HEADERS = [
        'List-Unsubscribe',
        'List-Unsubscribe-Post',
        'List-Id',
        'In-Reply-To',
        'References',
        'Precedence',
        'Auto-Submitted',
    ];

    /**
     * X-* header prefixes that PostRun reserves, so they are never sent along.
     */
    public const RESERVED_HEADER_PREFIXES = [
        'x-postrun-',
        'x-ses-',
        'x-capsule-',
    ];

    protected PostRunClient $client;

    public function __construct(
        string $apiKey,
        string $endpoint
    ) {
        parent::__construct();

        $this->client = new PostRunClient($apiKey, $endpoint);
    }

    public function __toString(): string
    {
        return 'postrun';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $payload = $this->buildPayload($email);

        try {
            $result = $this->client->send($payload);

            // Store the message ID in the message headers for reference
            $email->getHeaders()->addTextHeader('X-PostRun-Message-Id', $result['message_id']);
        } catch (PostRunException $e) {
            throw new \RuntimeException(
                'PostRun API error: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    protected function buildPayload(Email $email): array
    {
        $payload = [
            'subject' => $email->getSubject(),
        ];

        // To recipients (PostRun handles single recipient per message)
        $to = $email->getTo();
        if (! empty($to)) {
            $recipient = $to[0];
            $payload['to'] = [
                'email' => $recipient->getAddress(),
                'name' => $recipient->getName(),
            ];
        }

        // From address
        $from = $email->getFrom();
        if (! empty($from)) {
            $sender = $from[0];
            $payload['from'] = [
                'email' => $sender->getAddress(),
                'name' => $sender->getName(),
            ];
        }

        // Reply-To address
        $replyTo = $email->getReplyTo();
        if (! empty($replyTo)) {
            $replyToAddress = $replyTo[0];
            $payload['reply_to'] = [
                'email' => $replyToAddress->getAddress(),
                'name' => $replyToAddress->getName(),
            ];
        }

        // Body content
        if ($email->getHtmlBody()) {
            $payload['html'] = $email->getHtmlBody();
        }
        if ($email->getTextBody()) {
            $payload['text'] = $email->getTextBody();
        }

        // Attachments
        $attachments = $this->getAttachments($email);
        if (! empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        // Tags and metadata from Symfony headers
        $tags = $this->extractTags($email);
        if (! empty($tags)) {
            $payload['tags'] = $tags;
        }

        $meta = $this->extractMetadata($email);
        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        $priority = $this->extractPriority($email);
        if ($priority !== null) {
            $payload['priority'] = $priority->value;
        }

        $headers = $this->extractCustomHeaders($email);
        if (! empty($headers)) {
            $payload['headers'] = $headers;
        }

        return $payload;
    }

    protected function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            if ($attachment instanceof DataPart) {
                $attachments[] = [
                    'filename' => $attachment->getFilename() ?? 'attachment',
                    'content' => base64_encode($attachment->getBody()),
                    'mime_type' => $attachment->getMediaType().'/'.$attachment->getMediaSubtype(),
                ];
            }
        }

        return $attachments;
    }

    /**
     * Extract tags from the email headers.
     *
     * @return array<string>
     */
    protected function extractTags(Email $email): array
    {
        $headers = $email->getHeaders();

        if ($headers->has(self::TAGS_HEADER)) {
            $tagsHeader = $headers->get(self::TAGS_HEADER);
            if ($tagsHeader) {
                return array_map('trim', explode(',', $tagsHeader->getBodyAsString()));
            }
        }

        return [];
    }

    /**
     * Extract metadata from the email headers.
     *
     * @return array<string, mixed>
     */
    protected function extractMetadata(Email $email): array
    {
        $headers = $email->getHeaders();

        if ($headers->has(self::METADATA_HEADER)) {
            $metaHeader = $headers->get(self::METADATA_HEADER);
            if ($metaHeader) {
                return json_decode($metaHeader->getBodyAsString(), true) ?? [];
            }
        }

        return [];
    }

    /**
     * Extract the headers PostRun passes on to the email provider, such as
     * List-Unsubscribe and the app's own X-* headers.
     *
     * @return array<string, string>
     */
    protected function extractCustomHeaders(Email $email): array
    {
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            if ($this->isForwardedHeader($header->getName())) {
                $headers[$header->getName()] = $header->getBodyAsString();
            }
        }

        return $headers;
    }

    /**
     * Whether the header is sent along to PostRun.
     */
    protected function isForwardedHeader(string $name): bool
    {
        $lower = strtolower($name);

        if (in_array($lower, array_map('strtolower', self::FORWARDED_HEADERS), true)) {
            return true;
        }

        if (! str_starts_with($lower, 'x-')) {
            return false;
        }

        foreach (self::RESERVED_HEADER_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract the sending priority from the email headers.
     *
     * @throws \InvalidArgumentException When the header holds an unknown priority.
     */
    protected function extractPriority(Email $email): ?Priority
    {
        $priorityHeader = $email->getHeaders()->get(self::PRIORITY_HEADER);

        if ($priorityHeader === null || trim($priorityHeader->getBodyAsString()) === '') {
            return null;
        }

        return Priority::fromValue($priorityHeader->getBodyAsString());
    }
}
