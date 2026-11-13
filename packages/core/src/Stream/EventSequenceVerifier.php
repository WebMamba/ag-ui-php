<?php

declare(strict_types=1);

namespace AgUi\Stream;

use AgUi\Event\ActivityDeltaEvent;
use AgUi\Event\ActivitySnapshotEvent;
use AgUi\Event\Event;
use AgUi\Event\EventType;
use AgUi\Event\MessagesSnapshotEvent;
use AgUi\Event\ReasoningEncryptedValueEvent;
use AgUi\Event\ReasoningEncryptedValueSubtype;
use AgUi\Event\ReasoningEndEvent;
use AgUi\Event\ReasoningMessageContentEvent;
use AgUi\Event\ReasoningMessageEndEvent;
use AgUi\Event\ReasoningMessageStartEvent;
use AgUi\Event\ReasoningStartEvent;
use AgUi\Event\RunErrorEvent;
use AgUi\Event\RunFinishedEvent;
use AgUi\Event\RunStartedEvent;
use AgUi\Event\StepFinishedEvent;
use AgUi\Event\StepStartedEvent;
use AgUi\Event\SubagentErrorEvent;
use AgUi\Event\SubagentFinishedEvent;
use AgUi\Event\SubagentStartedEvent;
use AgUi\Event\TextMessageContentEvent;
use AgUi\Event\TextMessageEndEvent;
use AgUi\Event\TextMessageStartEvent;
use AgUi\Event\ToolCallArgsEvent;
use AgUi\Event\ToolCallEndEvent;
use AgUi\Event\ToolCallResultEvent;
use AgUi\Event\ToolCallStartEvent;
use AgUi\Exception\InvalidEventSequenceException;
use AgUi\Message\ActivityMessage;
use AgUi\Message\AssistantMessage;
use AgUi\Message\ReasoningMessage;

/**
 * Checks that events come in an order the protocol allows: a port of the TypeScript
 * client's verifyEvents, rule for rule, so a stream this accepts is one the official
 * client accepts. It sees streams after chunk expansion (see ChunkExpander), as the
 * client does.
 *
 *  - A stream starts with RUN_STARTED (or RUN_ERROR) and may hold several runs in
 *    sequence, never nested. After RUN_FINISHED only a new run or one RUN_ERROR may
 *    follow; after RUN_ERROR only a new run.
 *  - Text messages, tool calls, reasoning messages and reasoning spans are opened,
 *    continued and closed by id; several may be open at once. RUN_FINISHED requires all
 *    of them closed, and every step and subagent too.
 *  - Steps are matched by name per producer; subagents are started once per run.
 *  - A continuation attributed to a subagent must agree with whoever opened the entity.
 */
final class EventSequenceVerifier
{
    private const string NO_TEXT_MESSAGE = "No active text message found with ID '%s'. Start a text message with 'TEXT_MESSAGE_START' first.";
    private const string NO_TOOL_CALL = "No active tool call found with ID '%s'. Start a tool call with 'TOOL_CALL_START' first.";
    private const string NO_REASONING_MESSAGE = "No active reasoning message found with ID '%s'. Start a reasoning message with 'REASONING_MESSAGE_START' first.";

    /** @var array<string, true> */
    private array $activeMessages = [];
    /** @var array<string, true> */
    private array $activeToolCalls = [];
    /** @var array<string, true> */
    private array $activeReasoningSpans = [];
    /** @var array<string, true> */
    private array $activeReasoningMessages = [];

    /**
     * Who opened each entity, per kind, kept for the whole run: a continuation may come
     * after its entity closed (an encrypted value after TOOL_CALL_END). A null owner is
     * the parent agent, which is an owner like any other.
     *
     * @var array{message: array<string, ?string>, toolCall: array<string, ?string>, activity: array<string, ?string>, reasoning: array<string, ?string>}
     */
    private array $owners = ['message' => [], 'toolCall' => [], 'activity' => [], 'reasoning' => []];

    /** @var array<string, array<string, true>> producer key => step names */
    private array $activeSteps = [];
    /** @var array<string, true> */
    private array $activeSubagents = [];
    /** @var array<string, true> subagents finished in this run: their ids cannot be reused */
    private array $closedSubagents = [];

    private bool $firstEventReceived = false;
    private bool $runStarted = false;
    private bool $runFinished = false;
    private bool $runError = false;

    /**
     * @throws InvalidEventSequenceException
     */
    public function verify(Event $event): void
    {
        $type = $event->type();

        if ($this->runError && EventType::RunStarted !== $type) {
            $this->fail("Cannot send event type '%s': The run has already errored with 'RUN_ERROR'. No further events can be sent.", $type->value);
        }

        if ($this->runFinished && EventType::RunError !== $type && EventType::RunStarted !== $type) {
            $this->fail("Cannot send event type '%s': The run has already finished with 'RUN_FINISHED'. Start a new run with 'RUN_STARTED'.", $type->value);
        }

        if (!$this->firstEventReceived) {
            $this->firstEventReceived = true;
            if (EventType::RunStarted !== $type && EventType::RunError !== $type) {
                $this->fail("First event must be 'RUN_STARTED'.");
            }
        } elseif (EventType::RunStarted === $type) {
            if ($this->runStarted && !$this->runFinished && !$this->runError) {
                $this->fail("Cannot send 'RUN_STARTED' while a run is still active. The previous run must be finished with 'RUN_FINISHED' before starting a new run.");
            }
            if ($this->runFinished || $this->runError) {
                $this->resetRun();
            }
        }

        $tag = Attribution::of($event);

        match (true) {
            $event instanceof TextMessageStartEvent => $this->open($this->activeMessages, 'message', $event->messageId, $tag, $type, "A text message with ID '%s' is already in progress. Complete it with 'TEXT_MESSAGE_END' first."),
            $event instanceof TextMessageContentEvent => $this->continue($this->activeMessages, 'message', $event->messageId, $tag, $type, self::NO_TEXT_MESSAGE, 'text message'),
            $event instanceof TextMessageEndEvent => $this->close($this->activeMessages, 'message', $event->messageId, $tag, $type, self::NO_TEXT_MESSAGE, 'text message'),

            $event instanceof ToolCallStartEvent => $this->startToolCall($event),
            $event instanceof ToolCallArgsEvent => $this->continue($this->activeToolCalls, 'toolCall', $event->toolCallId, $tag, $type, self::NO_TOOL_CALL, 'tool call'),
            $event instanceof ToolCallEndEvent => $this->close($this->activeToolCalls, 'toolCall', $event->toolCallId, $tag, $type, self::NO_TOOL_CALL, 'tool call'),
            $event instanceof ToolCallResultEvent => $this->owners['message'][$event->messageId] = $tag,

            $event instanceof StepStartedEvent => $this->startStep($event, $tag),
            $event instanceof StepFinishedEvent => $this->finishStep($event, $tag),

            $event instanceof ActivitySnapshotEvent => $this->activitySnapshot($event, $tag),
            $event instanceof ActivityDeltaEvent => $this->agree($type, $tag, 'activity', $event->messageId, 'activity'),

            $event instanceof ReasoningStartEvent => $this->open($this->activeReasoningSpans, 'reasoning', $event->messageId, $tag, $type, "A reasoning span with ID '%s' is already in progress. Complete it with 'REASONING_END' first."),
            $event instanceof ReasoningEndEvent => $this->close($this->activeReasoningSpans, 'reasoning', $event->messageId, $tag, $type, "No active reasoning span found with ID '%s'. A 'REASONING_START' event must be sent first.", 'reasoning message'),
            $event instanceof ReasoningMessageStartEvent => $this->open($this->activeReasoningMessages, 'reasoning', $event->messageId, $tag, $type, "A reasoning message with ID '%s' is already in progress. Complete it with 'REASONING_MESSAGE_END' first."),
            $event instanceof ReasoningMessageContentEvent => $this->continue($this->activeReasoningMessages, 'reasoning', $event->messageId, $tag, $type, self::NO_REASONING_MESSAGE, 'reasoning message'),
            $event instanceof ReasoningMessageEndEvent => $this->close($this->activeReasoningMessages, 'reasoning', $event->messageId, $tag, $type, self::NO_REASONING_MESSAGE, 'reasoning message'),
            $event instanceof ReasoningEncryptedValueEvent => $this->encryptedValue($event, $tag),

            $event instanceof SubagentStartedEvent => $this->startSubagent($event),
            $event instanceof SubagentFinishedEvent, $event instanceof SubagentErrorEvent => $this->finishSubagent($type, $event->subagentRunId),

            $event instanceof MessagesSnapshotEvent => $this->seedOwners($event),
            $event instanceof RunStartedEvent => $this->runStarted = true,
            $event instanceof RunFinishedEvent => $this->finishRun(),
            $event instanceof RunErrorEvent => $this->runError = true,

            // CUSTOM, RAW, STATE_SNAPSHOT, STATE_DELTA, and chunks (expanded before they get
            // here): nothing to track.
            default => null,
        };
    }

    /**
     * @param array<string, true> $active
     * @param 'message'|'toolCall'|'activity'|'reasoning' $kind
     */
    private function open(array &$active, string $kind, string $id, ?string $tag, EventType $type, string $alreadyOpen): void
    {
        if (isset($active[$id])) {
            $this->fail("Cannot send '%s' event: ".$alreadyOpen, $type->value, $id);
        }

        if (\array_key_exists($id, $this->owners[$kind])) {
            $this->agree($type, $tag, $kind, $id, 'reasoning' === $kind ? 'reasoning message' : $kind);
        } else {
            $this->owners[$kind][$id] = $tag;
        }

        $active[$id] = true;
    }

    /**
     * @param array<string, true> $active
     * @param 'message'|'toolCall'|'activity'|'reasoning' $kind
     */
    private function continue(array $active, string $kind, string $id, ?string $tag, EventType $type, string $notOpen, string $entityKind): void
    {
        if (!isset($active[$id])) {
            $this->fail("Cannot send '%s' event: ".$notOpen, $type->value, $id);
        }

        $this->agree($type, $tag, $kind, $id, $entityKind);
    }

    /**
     * @param array<string, true> $active
     * @param 'message'|'toolCall'|'activity'|'reasoning' $kind
     */
    private function close(array &$active, string $kind, string $id, ?string $tag, EventType $type, string $notOpen, string $entityKind): void
    {
        $this->continue($active, $kind, $id, $tag, $type, $notOpen, $entityKind);

        unset($active[$id]);
    }

    private function startStep(StepStartedEvent $event, ?string $tag): void
    {
        if (isset($this->activeSteps[self::producer($tag)][$event->stepName])) {
            $this->fail('Step "%s" is already active for \'STEP_STARTED\'%s.', $event->stepName, null !== $tag ? " in subagent '{$tag}'" : '');
        }

        $this->activeSteps[self::producer($tag)][$event->stepName] = true;
    }

    private function activitySnapshot(ActivitySnapshotEvent $event, ?string $tag): void
    {
        // A snapshot that replaces the activity takes it over; one that merges does not.
        if (!\array_key_exists($event->messageId, $this->owners['activity']) || false !== $event->replace) {
            $this->owners['activity'][$event->messageId] = $tag;
        }
    }

    private function finishSubagent(EventType $type, string $id): void
    {
        if (!isset($this->activeSubagents[$id])) {
            $this->fail("Cannot send '%s': no active subagent found with ID '%s'. A 'SUBAGENT_STARTED' event must be sent first.", $type->value, $id);
        }

        unset($this->activeSubagents[$id]);
        $this->closedSubagents[$id] = true;
    }

    /**
     * A continuation attributed to a subagent must agree with the entity's opener. An
     * absent attribution always agrees.
     *
     * @param 'message'|'toolCall'|'activity'|'reasoning' $kind
     */
    private function agree(EventType $type, ?string $tag, string $kind, string $id, string $entityKind): void
    {
        if (null === $tag || !\array_key_exists($id, $this->owners[$kind])) {
            return;
        }

        $owner = $this->owners[$kind][$id];
        if ($owner !== $tag) {
            $this->fail("Cannot send '%s': subagentRunId '%s' does not match the %s '%s' opener's subagent '%s'.", $type->value, $tag, $entityKind, $id, $owner ?? '(the parent agent)');
        }
    }

    private function startToolCall(ToolCallStartEvent $event): void
    {
        $id = $event->toolCallId;
        $tag = $event->subagentRunId;

        if (isset($this->activeToolCalls[$id])) {
            $this->fail("Cannot send 'TOOL_CALL_START' event: A tool call with ID '%s' is already in progress. Complete it with 'TOOL_CALL_END' first.", $id);
        }

        // A tool call belongs to the message that carries it.
        $inherited = false;
        $inheritedOwner = null;
        if (null !== $event->parentMessageId && \array_key_exists($event->parentMessageId, $this->owners['message'])) {
            $parentOwner = $this->owners['message'][$event->parentMessageId];
            if (null !== $tag && $tag !== $parentOwner) {
                $this->fail("Cannot send 'TOOL_CALL_START': subagentRunId '%s' does not match its parent message '%s' owner '%s'. A tool call belongs to the message that carries it.", $tag, $event->parentMessageId, $parentOwner ?? '(the parent agent)');
            }
            $inherited = true;
            $inheritedOwner = $parentOwner;
        }

        if (\array_key_exists($id, $this->owners['toolCall'])) {
            $existing = $this->owners['toolCall'][$id];
            $this->agree(EventType::ToolCallStart, $tag, 'toolCall', $id, 'tool call');
            if (null === $tag && $inherited && $inheritedOwner !== $existing) {
                $this->fail("Cannot send 'TOOL_CALL_START': tool call '%s' is owned by '%s' but its parent message '%s' is owned by '%s'. A tool call belongs to the message that carries it.", $id, $existing ?? '(the parent agent)', (string) $event->parentMessageId, $inheritedOwner ?? '(the parent agent)');
            }
        } else {
            $this->owners['toolCall'][$id] = $tag ?? $inheritedOwner;
        }

        $this->activeToolCalls[$id] = true;
    }

    private function finishStep(StepFinishedEvent $event, ?string $tag): void
    {
        $name = $event->stepName;

        if (!isset($this->activeSteps[self::producer($tag)][$name])) {
            foreach ($this->activeSteps as $producer => $names) {
                if ($producer !== self::producer($tag) && isset($names[$name])) {
                    $this->fail("Cannot send 'STEP_FINISHED' for step \"%s\" attributed to %s: that step is open under %s. A step must be finished by whoever started it.", $name, null !== $tag ? "subagent '{$tag}'" : 'the parent agent', self::describeProducer($producer));
                }
            }

            $this->fail("Cannot send 'STEP_FINISHED' for step \"%s\" that was not started.", $name);
        }

        unset($this->activeSteps[self::producer($tag)][$name]);
    }

    private function encryptedValue(ReasoningEncryptedValueEvent $event, ?string $tag): void
    {
        $id = $event->entityId;

        [$kind, $label] = match ($event->subtype) {
            ReasoningEncryptedValueSubtype::ToolCall => ['toolCall', 'tool call'],
            ReasoningEncryptedValueSubtype::Message => [\array_key_exists($id, $this->owners['message']) ? 'message' : 'reasoning', 'message'],
        };

        $this->agree(EventType::ReasoningEncryptedValue, $tag, $kind, $id, $label);
    }

    private function startSubagent(SubagentStartedEvent $event): void
    {
        $id = $event->subagentRunId;

        if (isset($this->activeSubagents[$id])) {
            $this->fail("Cannot send 'SUBAGENT_STARTED': subagent '%s' is already active. Finish it with 'SUBAGENT_FINISHED' first.", $id);
        }
        if (isset($this->closedSubagents[$id])) {
            $this->fail("Cannot send 'SUBAGENT_STARTED': subagent '%s' has already finished in this run. Subagent IDs are per-invocation and cannot be reused.", $id);
        }
        if (null !== $event->parentSubagentRunId && !isset($this->activeSubagents[$event->parentSubagentRunId]) && !isset($this->closedSubagents[$event->parentSubagentRunId])) {
            $this->fail("Cannot send 'SUBAGENT_STARTED': parentSubagentRunId '%s' has not been started in this run.", $event->parentSubagentRunId);
        }

        $this->activeSubagents[$id] = true;
    }

    /**
     * A snapshot restates the conversation: its messages' owners replace whatever was
     * recorded, and each assistant message's tool calls belong to it.
     */
    private function seedOwners(MessagesSnapshotEvent $event): void
    {
        foreach ($event->messages as $message) {
            $owner = $message->subagentRunId;
            $kind = match (true) {
                $message instanceof ReasoningMessage => 'reasoning',
                $message instanceof ActivityMessage => 'activity',
                default => 'message',
            };
            $this->owners[$kind][$message->id] = $owner;

            if ($message instanceof AssistantMessage) {
                foreach ($message->toolCalls ?? [] as $toolCall) {
                    $this->owners['toolCall'][$toolCall->id] = $owner;
                }
            }
        }
    }

    private function finishRun(): void
    {
        $steps = [];
        foreach ($this->activeSteps as $producer => $names) {
            foreach (array_keys($names) as $name) {
                $steps[] = 'parent' === $producer ? $name : \sprintf('%s (%s)', $name, self::describeProducer($producer));
            }
        }

        foreach ([
            'steps' => $steps,
            'text messages' => array_keys($this->activeMessages),
            'reasoning messages' => array_keys($this->activeReasoningMessages),
            'reasoning spans' => array_keys($this->activeReasoningSpans),
            'tool calls' => array_keys($this->activeToolCalls),
            'subagents' => array_keys($this->activeSubagents),
        ] as $what => $open) {
            if ([] !== $open) {
                $this->fail("Cannot send 'RUN_FINISHED' while %s are still active: %s.", $what, implode(', ', $open));
            }
        }

        $this->runFinished = true;
    }

    private function resetRun(): void
    {
        $this->activeMessages = [];
        $this->activeToolCalls = [];
        $this->activeReasoningSpans = [];
        $this->activeReasoningMessages = [];
        $this->owners = ['message' => [], 'toolCall' => [], 'activity' => [], 'reasoning' => []];
        $this->activeSteps = [];
        $this->activeSubagents = [];
        $this->closedSubagents = [];
        $this->runFinished = $this->runError = false;
        $this->runStarted = true;
    }

    /** Keeps the parent agent apart from a subagent whose id is the empty string. */
    private static function producer(?string $tag): string
    {
        return null === $tag ? 'parent' : 'subagent:'.$tag;
    }

    private static function describeProducer(string $producer): string
    {
        return 'parent' === $producer ? 'the parent agent' : \sprintf("subagent '%s'", substr($producer, \strlen('subagent:')));
    }

    private function fail(string $message, string|int ...$arguments): never
    {
        throw new InvalidEventSequenceException(\sprintf($message, ...$arguments));
    }
}
