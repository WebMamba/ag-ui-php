<?php

declare(strict_types=1);

namespace AgUi\Exception;

/**
 * Data that does not have the shape the AG-UI JSON Schema requires: a missing or
 * unknown property, a value of the wrong type, an unknown discriminator.
 *
 * The location and keyword follow JSON Schema's output format, so an error reads the
 * same as the protocol's own fixture expectations.
 */
final class InvalidShapeException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param string $instanceLocation JSON Pointer to the offending value ("" for the root)
     * @param string $keyword the JSON Schema keyword that failed (required, type, const, oneOf, enum, unevaluatedProperties)
     */
    public function __construct(
        public readonly string $instanceLocation,
        public readonly string $keyword,
        string $message,
    ) {
        parent::__construct(\sprintf('%s: %s', '' === $instanceLocation ? '(root)' : $instanceLocation, $message));
    }
}
