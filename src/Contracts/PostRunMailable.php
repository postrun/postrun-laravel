<?php

namespace PostRun\Laravel\Contracts;

/**
 * Interface for Mailables that support PostRun tags and metadata.
 *
 * Implement this interface alongside the HasPostRunFeatures trait
 * to enable type-safe PostRun feature detection.
 */
interface PostRunMailable
{
    /**
     * Set PostRun tags at send time.
     *
     * @param  array  $tags
     * @return $this
     */
    public function setPostRunTags(array $tags);

    /**
     * Set PostRun metadata at send time.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function setPostRunMetadata(array $metadata);
}
