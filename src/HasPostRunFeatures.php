<?php

namespace PostRun\Laravel;

/**
 * Trait to enable postRunTags() and postRunMetadata() method support on Laravel Mailables.
 *
 * This trait adds custom X-PostRun-Tags and X-PostRun-Meta headers that the
 * PostRun transport will extract when sending.
 *
 * Usage:
 *   class MyMailable extends Mailable
 *   {
 *       use HasPostRunFeatures;
 *
 *       public function postRunTags(): array
 *       {
 *           return ['tag1', 'tag2'];
 *       }
 *
 *       public function postRunMetadata(): array
 *       {
 *           return ['user_id' => 123];
 *       }
 *   }
 */
trait HasPostRunFeatures
{
    /**
     * Build the message and add PostRun-specific headers.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return $this
     */
    protected function buildPostRunHeaders($message)
    {
        $tags = [];
        $metadata = [];

        // Get tags from the postRunTags() method if it exists
        if (method_exists($this, 'postRunTags')) {
            $methodTags = $this->postRunTags();
            if (is_array($methodTags)) {
                $tags = array_merge($tags, $methodTags);
            }
        }

        // Get metadata from the postRunMetadata() method if it exists
        if (method_exists($this, 'postRunMetadata')) {
            $methodMetadata = $this->postRunMetadata();
            if (is_array($methodMetadata)) {
                $metadata = array_merge($metadata, $methodMetadata);
            }
        }

        // Add tags as X-PostRun-Tags header (comma-separated)
        if (! empty($tags)) {
            $message->getHeaders()->addTextHeader('X-PostRun-Tags', implode(',', array_unique($tags)));
        }

        // Add metadata as X-PostRun-Meta header (JSON encoded)
        if (! empty($metadata)) {
            $message->getHeaders()->addTextHeader('X-PostRun-Meta', json_encode($metadata));
        }

        return $this;
    }

    /**
     * Build the Symfony message.
     * Overrides the parent to inject PostRun headers.
     *
     * @return \Symfony\Component\Mime\Email
     */
    protected function buildSymfonyMessage($message)
    {
        $this->buildPostRunHeaders($message);

        return parent::buildSymfonyMessage($message);
    }
}
