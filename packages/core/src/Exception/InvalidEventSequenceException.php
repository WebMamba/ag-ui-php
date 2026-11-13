<?php

declare(strict_types=1);

namespace AgUi\Exception;

/**
 * An event that is valid on its own but not where it comes in the event stream: content
 * before its start, a second RUN_STARTED, a run finished with a tool call still open….
 */
final class InvalidEventSequenceException extends \LogicException implements ExceptionInterface
{
}
