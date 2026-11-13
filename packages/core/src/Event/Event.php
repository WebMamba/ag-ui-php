<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\Model;

/**
 * Any AG-UI event. Every member is normative: there is no optional tier and no event a
 * consumer may decline to implement. Discriminated by the type property.
 */
interface Event extends Model
{
    /** When the event was created, in milliseconds since the Unix epoch, if known. */
    public ?int $timestamp { get; }

    /** Which event this is: the value of its "type" property. */
    public function type(): EventType;

    /** A copy of this event with its creation time set, in milliseconds since the Unix epoch. */
    public function withTimestamp(int $timestamp): static;
}
