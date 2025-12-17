<?php

namespace PostRun\Laravel;

/**
 * Trait to enable postRunTags() and postRunMetadata() method support on Laravel Mailables.
 *
 * This trait adds custom X-PostRun-Tags and X-PostRun-Meta headers that the
 * PostRun transport will extract when sending. The headers are automatically
 * added when the email is sent - no additional setup required.
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
    }
}
