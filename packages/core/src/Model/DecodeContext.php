<?php

declare(strict_types=1);

namespace AgUi\Model;

/**
 * How strictly to decode data received from the other side of the protocol.
 *
 * Lenient by default, as the protocol asks of consumers: an unknown property or an
 * unknown kind of list member (from a newer peer, say), and an optional property written
 * as null, are dropped and recorded rather than rejected. Strict mode rejects them, as
 * the JSON Schema does.
 */
final class DecodeContext
{
    /** @var list<string> JSON Pointers of what was dropped */
    public private(set) array $droppedProperties = [];

    public function __construct(
        public readonly bool $strict = false,
    ) {
    }

    public function drop(string $instanceLocation): void
    {
        $this->droppedProperties[] = $instanceLocation;
    }
}
