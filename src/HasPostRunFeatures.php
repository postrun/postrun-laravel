<?php

namespace PostRun\Laravel;

use PostRun\Laravel\Contracts\PostRunMailable;
use PostRun\Laravel\Transport\PostRunTransport;
use PostRun\Priority;

/**
 * Trait to enable PostRun tags, metadata and priority on Laravel Mailables.
 *
 * This trait adds custom X-PostRun-Tags, X-PostRun-Meta and X-PostRun-Priority
 * headers that the PostRun transport will extract when sending. The headers
 * are automatically added when the email is sent.
 *
 * Usage:
 *   use PostRun\Laravel\Contracts\PostRunMailable;
 *   use PostRun\Laravel\HasPostRunFeatures;
 *   use PostRun\Priority;
 *
 *   class MyMailable extends Mailable implements PostRunMailable
 *   {
 *       use HasPostRunFeatures;
 *
 *       // Optional: define tags for this specific mailable
 *       public function postRunTags(): array
 *       {
 *           return ['welcome-email'];
 *       }
 *
 *       // Optional: define metadata for this specific mailable
 *       public function postRunMetadata(): array
 *       {
 *           return ['campaign' => 'onboarding'];
 *       }
 *
 *       // Optional: define the sending priority for this specific mailable
 *       public function postRunPriority(): Priority
 *       {
 *           return Priority::High;
 *       }
 *   }
 *
 * At send time, you can add additional tags/metadata:
 *
 *   if ($mailable instanceof PostRunMailable) {
 *       $mailable->setPostRunMetadata(['user_id' => $user->id]);
 *   }
 *   Mail::to($user)->send($mailable);
 *
 * @see \PostRun\Laravel\Contracts\PostRunMailable
 */
trait HasPostRunFeatures
{
    /**
     * Runtime PostRun tags set via setPostRunTags().
     */
    protected array $runtimePostRunTags = [];

    /**
     * Runtime PostRun metadata set via setPostRunMetadata().
     */
    protected array $runtimePostRunMetadata = [];

    /**
     * Runtime PostRun priority set via setPostRunPriority().
     */
    protected ?Priority $runtimePostRunPriority = null;

    /**
     * Set PostRun tags at send time.
     * These are merged with any tags defined in postRunTags().
     *
     * @param  array  $tags
     * @return $this
     */
    public function setPostRunTags(array $tags)
    {
        $this->runtimePostRunTags = $tags;

        return $this;
    }

    /**
     * Set PostRun metadata at send time.
     * These are merged with any metadata defined in postRunMetadata().
     *
     * @param  array  $metadata
     * @return $this
     */
    public function setPostRunMetadata(array $metadata)
    {
        $this->runtimePostRunMetadata = $metadata;

        return $this;
    }

    /**
     * Set the PostRun sending priority at send time.
     * Overrides any priority defined in postRunPriority().
     *
     * @param  \PostRun\Priority|string  $priority  A Priority case, or "high", "normal" or "low".
     * @return $this
     *
     * @throws \InvalidArgumentException When the priority is not a known priority.
     */
    public function setPostRunPriority($priority)
    {
        $this->runtimePostRunPriority = Priority::fromValue($priority);

        return $this;
    }

    /**
     * Send the message using the given mailer.
     * Overrides parent to automatically add PostRun headers.
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($mailer)
    {
        $this->withSymfonyMessage(function ($message) {
            $this->addPostRunHeaders($message);
        });

        return parent::send($mailer);
    }

    /**
     * Add PostRun-specific headers to the Symfony message.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return void
     */
    protected function addPostRunHeaders($message): void
    {
        $tags = $this->collectPostRunTags();
        $metadata = $this->collectPostRunMetadata();

        // Add tags as X-PostRun-Tags header (comma-separated)
        if (! empty($tags)) {
            $message->getHeaders()->addTextHeader('X-PostRun-Tags', implode(',', array_unique($tags)));
        }

        // Add metadata as X-PostRun-Meta header (JSON encoded)
        if (! empty($metadata)) {
            $message->getHeaders()->addTextHeader('X-PostRun-Meta', json_encode($metadata));
        }

        // Add the priority as X-PostRun-Priority header, replacing any earlier one
        $priority = $this->collectPostRunPriority();
        if ($priority !== null) {
            $message->getHeaders()->remove(PostRunTransport::PRIORITY_HEADER);
            $message->getHeaders()->addTextHeader(PostRunTransport::PRIORITY_HEADER, $priority->value);
        }
    }

    /**
     * Resolve the PostRun priority from all sources.
     * Priority (later overrides earlier): base -> mailable method -> runtime.
     * Without any, PostRun uses the domain's default priority.
     *
     * @throws \InvalidArgumentException When a source returns an unknown priority.
     */
    protected function collectPostRunPriority(): ?Priority
    {
        if ($this->runtimePostRunPriority !== null) {
            return $this->runtimePostRunPriority;
        }

        foreach (['postRunPriority', 'basePostRunPriority'] as $method) {
            if (method_exists($this, $method)) {
                $priority = $this->{$method}();

                if ($priority !== null) {
                    return Priority::fromValue($priority);
                }
            }
        }

        return null;
    }

    /**
     * Collect all PostRun tags from all sources.
     * Priority (later overrides earlier): base -> mailable method -> runtime
     *
     * @return array
     */
    protected function collectPostRunTags(): array
    {
        $tags = [];

        // Get base tags (from domain-specific traits like HasWhatPulsePostRunFeatures)
        if (method_exists($this, 'basePostRunTags')) {
            $baseTags = $this->basePostRunTags();
            if (is_array($baseTags)) {
                $tags = array_merge($tags, $baseTags);
            }
        }

        // Get mailable-specific tags
        if (method_exists($this, 'postRunTags')) {
            $mailableTags = $this->postRunTags();
            if (is_array($mailableTags)) {
                $tags = array_merge($tags, $mailableTags);
            }
        }

        // Get runtime tags (set via setPostRunTags())
        if (! empty($this->runtimePostRunTags)) {
            $tags = array_merge($tags, $this->runtimePostRunTags);
        }

        return $tags;
    }

    /**
     * Collect all PostRun metadata from all sources.
     * Priority (later overrides earlier): base -> mailable method -> runtime
     *
     * @return array
     */
    protected function collectPostRunMetadata(): array
    {
        $metadata = [];

        // Get base metadata (from domain-specific traits like HasWhatPulsePostRunFeatures)
        if (method_exists($this, 'basePostRunMetadata')) {
            $baseMetadata = $this->basePostRunMetadata();
            if (is_array($baseMetadata)) {
                $metadata = array_merge($metadata, $baseMetadata);
            }
        }

        // Get mailable-specific metadata (overrides base)
        if (method_exists($this, 'postRunMetadata')) {
            $mailableMetadata = $this->postRunMetadata();
            if (is_array($mailableMetadata)) {
                $metadata = array_merge($metadata, $mailableMetadata);
            }
        }

        // Get runtime metadata (set via setPostRunMetadata(), highest priority)
        if (! empty($this->runtimePostRunMetadata)) {
            $metadata = array_merge($metadata, $this->runtimePostRunMetadata);
        }

        return $metadata;
    }
}
