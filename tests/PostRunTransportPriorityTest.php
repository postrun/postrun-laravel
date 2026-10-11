<?php

namespace PostRun\Laravel\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PostRun\Laravel\Transport\PostRunTransport;
use Symfony\Component\Mime\Email;

class PostRunTransportPriorityTest extends TestCase
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
            ->subject('Reset your password')
            ->html('<p>Reset</p>');
    }

    public function test_sends_the_priority_from_the_header(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader(PostRunTransport::PRIORITY_HEADER, 'high');

        $this->assertSame('high', $this->payloadFor($email)['priority']);
    }

    public function test_normalises_the_header_value(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader(PostRunTransport::PRIORITY_HEADER, ' Low ');

        $this->assertSame('low', $this->payloadFor($email)['priority']);
    }

    public function test_leaves_the_priority_out_without_a_header(): void
    {
        $this->assertArrayNotHasKey('priority', $this->payloadFor($this->email()));
    }

    public function test_rejects_an_unknown_priority_instead_of_silently_using_the_default(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader(PostRunTransport::PRIORITY_HEADER, 'urgent');

        $this->expectException(InvalidArgumentException::class);

        $this->payloadFor($email);
    }
}
