<?php

declare(strict_types=1);

namespace AgUi\Exception;

use AgUi\Event\Event;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * An event whose values break the protocol's constraints (a timestamp out of range, a
 * malformed JSON Pointer…), caught before it reached the wire.
 */
final class InvalidEventException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(
        public readonly Event $event,
        public readonly ConstraintViolationListInterface $violations,
    ) {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = \sprintf('%s: %s', $violation->getPropertyPath() ?: '(event)', $violation->getMessage());
        }

        parent::__construct(\sprintf('Invalid %s event: %s', $event->type()->value, implode(' ', $messages)));
    }
}
