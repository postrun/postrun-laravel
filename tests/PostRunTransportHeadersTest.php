<?php

namespace PostRun\Laravel\Tests;

use PHPUnit\Framework\TestCase;
use PostRun\Laravel\Transport\PostRunTransport;
use Symfony\Component\Mime\Email;

class PostRunTransportHeadersTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Email $email): array
    {
        $transport = new PostRunTransport('test-key', 'https://postrun.test');

        return (fn (Email $email) => $this->buildPayload($email))->call($transport, $email);
    }

    private function email(): Email
    {
        return (new Email)
            ->from('app@example.com')
            ->to('user@example.com')
            ->subject('Weekly digest')
            ->html('<p>Digest</p>');
    }

    public function test_sends_list_unsubscribe_headers_along(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader('List-Unsubscribe', '<https://example.com/u/abc>, <mailto:unsubscribe@example.com>');
        $email->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

        $this->assertSame([
            'List-Unsubscribe' => '<https://example.com/u/abc>, <mailto:unsubscribe@example.com>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ], $this->payloadFor($email)['headers']);
    }

    public function test_sends_threading_and_custom_headers_along(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader('In-Reply-To', '<original@example.com>');
        $email->getHeaders()->addTextHeader('X-Entity-Ref-ID', 'digest-2026-42');

        $this->assertSame([
            'In-Reply-To' => '<original@example.com>',
            'X-Entity-Ref-ID' => 'digest-2026-42',
        ], $this->payloadFor($email)['headers']);
    }

    public function test_leaves_out_addressing_and_postrun_headers(): void
    {
        $email = $this->email()->replyTo('support@example.com');
        $email->getHeaders()->addTextHeader(PostRunTransport::TAGS_HEADER, 'digest');
        $email->getHeaders()->addTextHeader(PostRunTransport::PRIORITY_HEADER, 'low');
        $email->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', 'other-set');
        $email->getHeaders()->addTextHeader('Organization', 'Example');

        $payload = $this->payloadFor($email);

        $this->assertArrayNotHasKey('headers', $payload);
        $this->assertSame(['digest'], $payload['tags']);
    }
}
