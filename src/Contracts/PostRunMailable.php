<?php

namespace PostRun\Laravel\Contracts;

/**
 * Interface for Mailables that support PostRun tags, metadata and priority.
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

    /**
     * Set the PostRun sending priority at send time.
     *
     * @param  \PostRun\Priority|string  $priority  A Priority case, or "high", "normal" or "low".
     * @return $this
     */
    public function setPostRunPriority($priority);
}
