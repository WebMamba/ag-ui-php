<?php

declare(strict_types=1);

namespace AgUi\Model;

/**
 * Something that can belong to a subagent's work: the events that describe content or
 * progress, every message, and each interrupt.
 */
interface Attributable
{
    /** The subagent invocation this belongs to; null means the parent agent produced it. */
    public ?string $subagentRunId { get; }
}
