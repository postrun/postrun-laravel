<?php

namespace PostRun\Laravel\Transport;

use PostRun\Exceptions\PostRunException;
use PostRun\PostRunClient;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class PostRunTransport extends AbstractTransport
{
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

        if ($headers->has('X-PostRun-Tags')) {
            $tagsHeader = $headers->get('X-PostRun-Tags');
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

        if ($headers->has('X-PostRun-Meta')) {
            $metaHeader = $headers->get('X-PostRun-Meta');
            if ($metaHeader) {
                return json_decode($metaHeader->getBodyAsString(), true) ?? [];
            }
        }

        return [];
    }
}
