<?php

declare(strict_types=1);

namespace AgUi\Stream;

use AgUi\Event\Event;
use AgUi\Event\SubagentErrorEvent;
use AgUi\Event\SubagentFinishedEvent;
use AgUi\Event\SubagentStartedEvent;
use AgUi\Model\Attributable;

/**
 * @internal
 */
final class Attribution
{
    /**
     * The subagent an event belongs to: null for the parent agent, and for run-scoped
     * events, which belong to no one. A subagent's lifecycle events are about that
     * subagent, and carry it in the same property.
     */
    public static function of(Event $event): ?string
    {
        return match (true) {
            $event instanceof Attributable => $event->subagentRunId,
            $event instanceof SubagentStartedEvent, $event instanceof SubagentFinishedEvent, $event instanceof SubagentErrorEvent => $event->subagentRunId,
            default => null,
        };
    }
}
