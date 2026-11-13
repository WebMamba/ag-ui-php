<?php

declare(strict_types=1);

namespace AgUi\Tests\Stream;

use AgUi\Event\CustomEvent;
use AgUi\Event\Event;
use AgUi\Event\RunErrorEvent;
use AgUi\Event\RunFinishedEvent;
use AgUi\Event\RunStartedEvent;
use AgUi\Event\StateDeltaEvent;
use AgUi\Event\StepStartedEvent;
use AgUi\Event\TextMessageChunkEvent;
use AgUi\Event\TextMessageContentEvent;
use AgUi\Event\TextMessageEndEvent;
use AgUi\Event\TextMessageStartEvent;
use AgUi\Patch\AddOperation;
use AgUi\Protocol;
use AgUi\Run\Interrupt;
use AgUi\Run\RunAgentInput;
use AgUi\Run\RunFinishedInterruptOutcome;
use AgUi\Stream\RunStream;
use AgUi\Stream\RunStreamWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Clock\MockClock;

final class RunStreamTest extends TestCase
{
    public function testTheRunIsOpenedAndClosedAroundTheApplicationsEvents(): void
    {
        $events = $this->stream([
            new TextMessageStartEvent('m1'),
            new TextMessageContentEvent('m1', 'Ahoy'),
            new TextMessageEndEvent('m1'),
        ]);

        self::assertSame(['RUN_STARTED', 'TEXT_MESSAGE_START', 'TEXT_MESSAGE_CONTENT', 'TEXT_MESSAGE_END', 'RUN_FINISHED'], self::types($events));

        $started = $events[0];
        self::assertInstanceOf(RunStartedEvent::class, $started);
        self::assertSame(['thread_1', 'run_1', Protocol::VERSION, 'parent_1'], [$started->threadId, $started->runId, $started->protocolVersion, $started->parentRunId]);

        $finished = $events[4];
        self::assertInstanceOf(RunFinishedEvent::class, $finished);
        self::assertNull($finished->outcome, 'No outcome means success.');
    }

    public function testEveryEventIsStampedWithTheTimeItWasEmittedUnlessItHasOne(): void
    {
        $events = $this->stream([
            new CustomEvent('first', null),
            new CustomEvent('second', null, timestamp: 42),
        ]);

        self::assertSame([1_767_225_600_000, 1_767_225_600_000, 42, 1_767_225_600_000], array_map(static fn (Event $e) => $e->timestamp, $events));
    }

    public function testAnApplicationThatFinishesTheRunItselfIsNotFinishedTwice(): void
    {
        $events = $this->stream([
            new RunFinishedEvent('thread_1', 'run_1', outcome: new RunFinishedInterruptOutcome([new Interrupt('int_1', 'tool_approval')])),
        ]);

        self::assertSame(['RUN_STARTED', 'RUN_FINISHED'], self::types($events));
        self::assertInstanceOf(RunFinishedInterruptOutcome::class, $events[1]->outcome);
    }

    public function testAChunkedMessageStillOpenAtTheEndIsLegal(): void
    {
        // The client closes a chunked stream itself before RUN_FINISHED.
        $events = $this->stream([new TextMessageChunkEvent(messageId: 'm1', delta: 'Fair winds')]);

        self::assertSame(['RUN_STARTED', 'TEXT_MESSAGE_CHUNK', 'RUN_FINISHED'], self::types($events), 'Chunks are sent as they are: the client expands them.');
    }

    public function testAMessageLeftOpenEndsTheRunInAnInvalidSequenceError(): void
    {
        $events = $this->stream([new TextMessageStartEvent('m1')]);

        self::assertSame(['RUN_STARTED', 'TEXT_MESSAGE_START', 'RUN_ERROR'], self::types($events));
        self::assertSame(RunStream::CODE_INVALID_SEQUENCE, $events[2]->code);
        self::assertSame('The agent run failed.', $events[2]->message, 'No details outside debug mode.');
    }

    public function testDebugModeTellsTheClientWhatWentWrong(): void
    {
        $events = $this->stream([new TextMessageContentEvent('m1', 'orphan')], debug: true);

        self::assertSame(RunStream::CODE_INVALID_SEQUENCE, $events[1]->code);
        self::assertStringContainsString("No active text message found with ID 'm1'", $events[1]->message);
    }

    public function testAnInvalidEventIsCaughtBeforeTheWire(): void
    {
        $events = $this->stream([new StateDeltaEvent([new AddOperation('no-leading-slash', 1)])], debug: true);

        self::assertSame(['RUN_STARTED', 'RUN_ERROR'], self::types($events), 'The invalid event itself is never sent.');
        self::assertSame(RunStream::CODE_INVALID_EVENT, $events[1]->code);
        self::assertStringContainsString('delta[0].path', $events[1]->message);
    }

    public function testAnExceptionFromTheApplicationEndsTheRunAndIsLogged(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{string, array<string, mixed>}> */
            public array $logs = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = [$level, $context];
            }
        };

        $events = $this->stream((static function (): \Generator {
            yield new StepStartedEvent('routing');
            throw new \RuntimeException('The model provider is down.');
        })(), logger: $logger);

        self::assertSame(['RUN_STARTED', 'STEP_STARTED', 'RUN_ERROR'], self::types($events));
        self::assertSame(RunStream::CODE_AGENT_ERROR, $events[2]->code);
        self::assertSame('error', $logger->logs[0][0]);
        self::assertInstanceOf(\RuntimeException::class, $logger->logs[0][1]['exception']);
    }

    public function testAnApplicationsOwnRunErrorIsTerminal(): void
    {
        $events = $this->stream((static function (): \Generator {
            yield new RunErrorEvent('Out of fuel.', code: 'no_fuel');
            yield new CustomEvent('never sent', null);
            throw new \RuntimeException('Nor is a second RUN_ERROR.');
        })());

        self::assertSame(['RUN_STARTED', 'RUN_ERROR'], self::types($events));
        self::assertSame('no_fuel', $events[1]->code);
    }

    public function testTheWriterEncodesEachEventAsAServerSentEventsFrame(): void
    {
        $writer = new RunStreamWriter(new RunStream(clock: new MockClock('2026-01-01 00:00:00 UTC')));

        $frames = iterator_to_array($writer->write(self::input(), [new CustomEvent('ping', ['ok' => true])]), false);

        self::assertSame('text/event-stream', $writer->contentType());
        self::assertSame('data: {"type":"CUSTOM","name":"ping","value":{"ok":true},"timestamp":1767225600000}'."\n\n", $frames[1]);
    }

    /**
     * @param iterable<Event> $events
     *
     * @return list<Event>
     */
    private function stream(iterable $events, bool $debug = false, ?AbstractLogger $logger = null): array
    {
        $stream = new RunStream(clock: new MockClock('2026-01-01 00:00:00 UTC'), logger: $logger, debug: $debug);

        return iterator_to_array($stream->events(self::input(), $events), false);
    }

    private static function input(): RunAgentInput
    {
        return new RunAgentInput('thread_1', 'run_1', [], parentRunId: 'parent_1');
    }

    /**
     * @param list<Event> $events
     *
     * @return list<string>
     */
    private static function types(array $events): array
    {
        return array_map(static fn (Event $e) => $e->type()->value, $events);
    }
}
