<?php

declare(strict_types=1);

namespace AgUi\Stream;

use AgUi\Event\Event;
use AgUi\Event\RunErrorEvent;
use AgUi\Event\RunFinishedEvent;
use AgUi\Event\RunStartedEvent;
use AgUi\Exception\InvalidEventException;
use AgUi\Exception\InvalidEventSequenceException;
use AgUi\Protocol;
use AgUi\Run\RunAgentInput;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Turns the events an application produces for one run into the event stream the
 * client receives: the single point every event passes through before the wire.
 *
 *  - It opens the run with RUN_STARTED and closes it with RUN_FINISHED (success),
 *    unless the application finished it itself — with an interrupt outcome, say.
 *  - It stamps each event with the time it was emitted, unless it carries one.
 *  - It validates each event and verifies the order of the whole stream, exactly as the
 *    official client will (see ChunkExpander and EventSequenceVerifier).
 *  - Any failure — the application throwing, an invalid event, an impossible order —
 *    ends the run with RUN_ERROR, which is terminal. The message is generic unless in
 *    debug mode, the full exception is logged, and the code says which kind it was.
 */
final class RunStream
{
    public const string CODE_AGENT_ERROR = 'agent_error';
    public const string CODE_INVALID_EVENT = 'invalid_event';
    public const string CODE_INVALID_SEQUENCE = 'invalid_sequence';

    private ValidatorInterface $validator;
    private ClockInterface $clock;
    private LoggerInterface $logger;

    public function __construct(
        ?ValidatorInterface $validator = null,
        ?ClockInterface $clock = null,
        ?LoggerInterface $logger = null,
        private readonly bool $debug = false,
    ) {
        $this->validator = $validator ?? Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $this->clock = $clock ?? new NativeClock();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param iterable<Event> $events the run's events, without RUN_STARTED, and without
     *                                RUN_FINISHED unless the run ends otherwise than in success
     *
     * @return \Generator<int, Event>
     */
    public function events(RunAgentInput $input, iterable $events): \Generator
    {
        $expander = new ChunkExpander();
        $verifier = new EventSequenceVerifier();
        $finished = false;
        $errored = false;

        try {
            yield $this->emit(new RunStartedEvent($input->threadId, $input->runId, protocolVersion: Protocol::VERSION, parentRunId: $input->parentRunId), $expander, $verifier);

            foreach ($events as $event) {
                $event = $this->emit($event, $expander, $verifier);
                if ($event instanceof RunFinishedEvent) {
                    $finished = true;
                }
                if ($event instanceof RunErrorEvent) {
                    $finished = $errored = true;
                }

                yield $event;

                if ($errored) {
                    // RUN_ERROR is terminal: whatever the application still had to say is not sent.
                    return;
                }
            }

            if (!$finished) {
                yield $this->emit(new RunFinishedEvent($input->threadId, $input->runId), $expander, $verifier);
            }
        } catch (\Throwable $e) {
            $code = match (true) {
                $e instanceof InvalidEventException => self::CODE_INVALID_EVENT,
                $e instanceof InvalidEventSequenceException => self::CODE_INVALID_SEQUENCE,
                default => self::CODE_AGENT_ERROR,
            };

            $this->logger->error('AG-UI run {runId} failed: {message}', ['runId' => $input->runId, 'threadId' => $input->threadId, 'message' => $e->getMessage(), 'code' => $code, 'exception' => $e]);

            // RUN_ERROR is legal at any point, even after RUN_FINISHED — but only once.
            if (!$errored) {
                yield $this->stamp(new RunErrorEvent($this->debug ? $e->getMessage() : 'The agent run failed.', code: $code));
            }
        }
    }

    private function emit(Event $event, ChunkExpander $expander, EventSequenceVerifier $verifier): Event
    {
        $event = $this->stamp($event);

        $violations = $this->validator->validate($event);
        if (\count($violations) > 0) {
            throw new InvalidEventException($event, $violations);
        }

        foreach ($expander->expand($event) as $expanded) {
            $verifier->verify($expanded);
        }

        return $event;
    }

    private function stamp(Event $event): Event
    {
        return null === $event->timestamp ? $event->withTimestamp((int) $this->clock->now()->format('Uv')) : $event;
    }
}
