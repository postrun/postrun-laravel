<?php

namespace PostRun\Laravel;

use Symfony\Component\Mime\Header\MetadataHeader;
use Symfony\Component\Mime\Header\TagHeader;

/**
 * Trait to enable postRunTags() and postRunMetadata() method support on Laravel Mailables.
 *
 * Laravel's base Mailable only reads from the $tags and $metadata properties.
 * This trait overrides buildTags() and buildMetadata() to also check for
 * postRunTags() and postRunMetadata() methods, allowing a cleaner API without
 * conflicting with Laravel's native methods.
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
     * Override buildTags to support a postRunTags() method.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return $this
     */
    protected function buildTags($message)
    {
        $tags = [];

        // First, get tags from the postRunTags() method if it exists
        if (method_exists($this, 'postRunTags')) {
            $methodTags = $this->postRunTags();
            if (is_array($methodTags)) {
                $tags = array_merge($tags, $methodTags);
            }
        }

        // Also include tags from the $tags property (set via tag() fluent method)
        if (property_exists($this, 'tags') && is_array($this->tags)) {
            $tags = array_merge($tags, $this->tags);
        }

        // Add unique tags as headers
        foreach (array_unique($tags) as $tag) {
            $message->getHeaders()->add(new TagHeader($tag));
        }

        return $this;
    }

    /**
     * Override buildMetadata to support a postRunMetadata() method.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return $this
     */
    protected function buildMetadata($message)
    {
        $metadata = [];

        // First, get metadata from the postRunMetadata() method if it exists
        if (method_exists($this, 'postRunMetadata')) {
            $methodMetadata = $this->postRunMetadata();
            if (is_array($methodMetadata)) {
                $metadata = array_merge($metadata, $methodMetadata);
            }
        }

        // Also include metadata from the $metadata property
        if (property_exists($this, 'metadata') && is_array($this->metadata)) {
            $metadata = array_merge($metadata, $this->metadata);
        }

        // Add metadata as headers
        foreach ($metadata as $key => $value) {
            $message->getHeaders()->add(new MetadataHeader($key, $value));
        }

        return $this;
    }
}
