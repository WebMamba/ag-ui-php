<?php

declare(strict_types=1);

namespace AgUi\Stream;

use AgUi\Encoder\SseEncoder;
use AgUi\Event\Event;
use AgUi\Run\RunAgentInput;

/**
 * Writes a run's events as Server-Sent Events frames, through RunStream.
 *
 *     $frames = (new RunStreamWriter())->write($input, $events);
 *     foreach ($frames as $frame) { echo $frame; flush(); }
 */
final class RunStreamWriter
{
    public function __construct(
        private readonly RunStream $stream = new RunStream(),
        private readonly SseEncoder $encoder = new SseEncoder(),
    ) {
    }

    /**
     * @param iterable<Event> $events
     *
     * @return \Generator<int, string>
     */
    public function write(RunAgentInput $input, iterable $events): \Generator
    {
        foreach ($this->stream->events($input, $events) as $event) {
            yield $this->encoder->encode($event);
        }
    }

    public function contentType(): string
    {
        return $this->encoder->contentType();
    }
}
