<?php

namespace PostRun\Laravel\Tests;

use Illuminate\Mail\Mailable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PostRun\Laravel\Contracts\PostRunMailable;
use PostRun\Laravel\HasPostRunFeatures;
use PostRun\Laravel\Transport\PostRunTransport;
use PostRun\Priority;
use Symfony\Component\Mime\Email;

class HasPostRunFeaturesPriorityTest extends TestCase
{
    /**
     * Apply the mailable's PostRun headers to a fresh message and return it.
     */
    private function headersOf(Mailable $mailable): Email
    {
        $email = new Email;

        (fn (Email $message) => $this->addPostRunHeaders($message))->call($mailable, $email);

        return $email;
    }

    private function priorityHeader(Email $email): ?string
    {
        return $email->getHeaders()->get(PostRunTransport::PRIORITY_HEADER)?->getBodyAsString();
    }

    public function test_adds_no_priority_header_by_default(): void
    {
        $this->assertNull($this->priorityHeader($this->headersOf(new PlainPostRunMailable)));
    }

    public function test_uses_the_priority_defined_on_the_mailable(): void
    {
        $this->assertSame('high', $this->priorityHeader($this->headersOf(new HighPriorityMailable)));
    }

    public function test_accepts_a_priority_name_from_the_mailable(): void
    {
        $this->assertSame('low', $this->priorityHeader($this->headersOf(new StringPriorityMailable)));
    }

    public function test_the_runtime_priority_overrides_the_mailable(): void
    {
        $mailable = (new HighPriorityMailable)->setPostRunPriority(Priority::Low);

        $this->assertSame('low', $this->priorityHeader($this->headersOf($mailable)));
    }

    public function test_the_runtime_priority_accepts_a_name(): void
    {
        $mailable = (new PlainPostRunMailable)->setPostRunPriority('NORMAL');

        $this->assertSame('normal', $this->priorityHeader($this->headersOf($mailable)));
    }

    public function test_the_mailable_priority_overrides_the_base_priority(): void
    {
        $this->assertSame('high', $this->priorityHeader($this->headersOf(new BaseAndOwnPriorityMailable)));
    }

    public function test_the_base_priority_applies_when_the_mailable_defines_none(): void
    {
        $this->assertSame('low', $this->priorityHeader($this->headersOf(new BasePriorityOnlyMailable)));
    }

    public function test_rejects_an_unknown_runtime_priority(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PlainPostRunMailable)->setPostRunPriority('urgent');
    }

    public function test_applying_the_headers_twice_keeps_one_priority_header(): void
    {
        $mailable = new HighPriorityMailable;
        $email = new Email;
        $apply = fn (Email $message) => $this->addPostRunHeaders($message);

        $apply->call($mailable, $email);
        $apply->call($mailable, $email);

        $this->assertCount(1, $email->getHeaders()->all(PostRunTransport::PRIORITY_HEADER));
    }

    public function test_the_runtime_priority_survives_queue_serialization(): void
    {
        $mailable = unserialize(serialize((new PlainPostRunMailable)->setPostRunPriority(Priority::High)));

        $this->assertSame('high', $this->priorityHeader($this->headersOf($mailable)));
    }
}

class PlainPostRunMailable extends Mailable implements PostRunMailable
{
    use HasPostRunFeatures;
}

class HighPriorityMailable extends Mailable implements PostRunMailable
{
    use HasPostRunFeatures;

    public function postRunPriority(): Priority
    {
        return Priority::High;
    }
}

class StringPriorityMailable extends Mailable implements PostRunMailable
{
    use HasPostRunFeatures;

    public function postRunPriority(): string
    {
        return 'low';
    }
}

class BasePriorityOnlyMailable extends Mailable implements PostRunMailable
{
    use HasPostRunFeatures;

    protected function basePostRunPriority(): Priority
    {
        return Priority::Low;
    }
}

class BaseAndOwnPriorityMailable extends BasePriorityOnlyMailable
{
    public function postRunPriority(): Priority
    {
        return Priority::High;
    }
}
