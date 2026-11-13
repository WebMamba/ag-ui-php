<?php

declare(strict_types=1);

namespace AgUi\Stream;

use AgUi\Event\Event;
use AgUi\Event\EventType;
use AgUi\Event\ReasoningMessageChunkEvent;
use AgUi\Event\ReasoningMessageContentEvent;
use AgUi\Event\ReasoningMessageEndEvent;
use AgUi\Event\ReasoningMessageStartEvent;
use AgUi\Event\TextMessageChunkEvent;
use AgUi\Event\TextMessageContentEvent;
use AgUi\Event\TextMessageEndEvent;
use AgUi\Event\TextMessageStartEvent;
use AgUi\Event\ToolCallArgsEvent;
use AgUi\Event\ToolCallChunkEvent;
use AgUi\Event\ToolCallEndEvent;
use AgUi\Event\ToolCallStartEvent;
use AgUi\Exception\InvalidEventSequenceException;
use AgUi\Message\TextMessageRole;

/**
 * Expands *_CHUNK events into the START / CONTENT (or ARGS) / END events they stand
 * for, as a client does before it verifies a stream: a port of the TypeScript client's
 * transformChunks, rule for rule.
 *
 * Each producer (the parent agent, each subagent) has its own lane holding at most one
 * pending chunked stream. A chunk without an id, or with the pending stream's id,
 * continues it; anything else closes it first. Explicit events close their producer's
 * lane, run-level events close every lane — which is why a chunked message still open at
 * RUN_FINISHED is legal: its END is synthesised just before.
 */
final class ChunkExpander
{
    /**
     * The pending stream of each producer's lane, with what its opener established.
     *
     * @var array<string, array{kind: 'text', id: string, owner: ?string, role: TextMessageRole, name: ?string}|array{kind: 'tool', id: string, owner: ?string, toolCallName: string, parentMessageId: ?string}|array{kind: 'reasoning', id: string, owner: ?string}>
     */
    private array $lanes = [];

    /**
     * @return list<Event> what the client will see in place of this event
     */
    public function expand(Event $event): array
    {
        return match (true) {
            $event instanceof TextMessageChunkEvent => $this->textChunk($event),
            $event instanceof ToolCallChunkEvent => $this->toolChunk($event),
            $event instanceof ReasoningMessageChunkEvent => $this->reasoningChunk($event),

            \in_array($event->type(), [EventType::RunStarted, EventType::RunFinished, EventType::RunError, EventType::MessagesSnapshot], true) => [...$this->closeAllLanes(), $event],

            \in_array($event->type(), [EventType::Raw, EventType::ActivitySnapshot, EventType::ActivityDelta, EventType::ReasoningEncryptedValue, EventType::SubagentStarted], true) => [$event],

            // Everything else closes its producer's lane; a subagent's terminal event closes
            // that subagent's.
            default => [...$this->closeLane(Attribution::of($event)), $event],
        };
    }

    public function reset(): void
    {
        $this->lanes = [];
    }

    /** @return list<Event> */
    private function textChunk(TextMessageChunkEvent $chunk): array
    {
        $lane = $this->resolveLane('text', $chunk->messageId, $chunk->subagentRunId, 'TEXT_MESSAGE_CHUNK', 'text message');
        $open = $this->lanes[self::key($lane)] ?? null;
        $result = [];

        if (null !== $open && 'text' === $open['kind'] && (null === $chunk->messageId || $chunk->messageId === $open['id'])) {
            self::requireAgreement('text message', $open['id'], 'role', $chunk->role?->value, $open['role']->value);
            self::requireAgreement('text message', $open['id'], 'name', $chunk->name, $open['name']);
            $fields = $open;
        } else {
            $result = $this->closeLane($lane);
            if (null === $chunk->messageId) {
                throw new InvalidEventSequenceException('First TEXT_MESSAGE_CHUNK must have a messageId.');
            }

            $fields = ['kind' => 'text', 'id' => $chunk->messageId, 'owner' => $chunk->subagentRunId, 'role' => $chunk->role ?? TextMessageRole::Assistant, 'name' => $chunk->name];
            $this->lanes[self::key($lane)] = $fields;
            $result[] = new TextMessageStartEvent($chunk->messageId, role: $fields['role'], name: $chunk->name, metadata: $chunk->metadata, subagentRunId: $chunk->subagentRunId);
        }

        if (null !== $chunk->delta || null !== $chunk->rawEvent || ([] === $result && null !== $chunk->metadata)) {
            $result[] = new TextMessageContentEvent($fields['id'], $chunk->delta ?? '', rawEvent: $chunk->rawEvent, metadata: $chunk->metadata, subagentRunId: $chunk->subagentRunId ?? $fields['owner']);
        }

        return $result;
    }

    /** @return list<Event> */
    private function toolChunk(ToolCallChunkEvent $chunk): array
    {
        $lane = $this->resolveLane('tool', $chunk->toolCallId, $chunk->subagentRunId, 'TOOL_CALL_CHUNK', 'tool call');
        $open = $this->lanes[self::key($lane)] ?? null;
        $result = [];

        if (null !== $open && 'tool' === $open['kind'] && (null === $chunk->toolCallId || $chunk->toolCallId === $open['id'])) {
            self::requireAgreement('tool call', $open['id'], 'toolCallName', $chunk->toolCallName, $open['toolCallName']);
            self::requireAgreement('tool call', $open['id'], 'parentMessageId', $chunk->parentMessageId, $open['parentMessageId']);
            $fields = $open;
        } else {
            $result = $this->closeLane($lane);
            if (null === $chunk->toolCallId) {
                throw new InvalidEventSequenceException('First TOOL_CALL_CHUNK must have a toolCallId.');
            }
            if (null === $chunk->toolCallName) {
                throw new InvalidEventSequenceException('First TOOL_CALL_CHUNK must have a toolCallName.');
            }

            $fields = ['kind' => 'tool', 'id' => $chunk->toolCallId, 'owner' => $chunk->subagentRunId, 'toolCallName' => $chunk->toolCallName, 'parentMessageId' => $chunk->parentMessageId];
            $this->lanes[self::key($lane)] = $fields;
            $result[] = new ToolCallStartEvent($chunk->toolCallId, $chunk->toolCallName, parentMessageId: $chunk->parentMessageId, metadata: $chunk->metadata, subagentRunId: $chunk->subagentRunId);
        }

        if (null !== $chunk->delta || null !== $chunk->rawEvent || ([] === $result && null !== $chunk->metadata)) {
            $result[] = new ToolCallArgsEvent($fields['id'], $chunk->delta ?? '', rawEvent: $chunk->rawEvent, metadata: $chunk->metadata, subagentRunId: $chunk->subagentRunId ?? $fields['owner']);
        }

        return $result;
    }

    /** @return list<Event> */
    private function reasoningChunk(ReasoningMessageChunkEvent $chunk): array
    {
        $lane = $this->resolveLane('reasoning', $chunk->messageId, $chunk->subagentRunId, 'REASONING_MESSAGE_CHUNK', 'reasoning message');
        $open = $this->lanes[self::key($lane)] ?? null;
        $result = [];

        if (null !== $open && 'reasoning' === $open['kind'] && (null === $chunk->messageId || $chunk->messageId === $open['id'])) {
            $fields = $open;
        } else {
            $result = $this->closeLane($lane);
            if (null === $chunk->messageId) {
                throw new InvalidEventSequenceException('First REASONING_MESSAGE_CHUNK must have a messageId.');
            }

            $fields = ['kind' => 'reasoning', 'id' => $chunk->messageId, 'owner' => $chunk->subagentRunId];
            $this->lanes[self::key($lane)] = $fields;
            $result[] = new ReasoningMessageStartEvent($chunk->messageId, metadata: $chunk->metadata, subagentRunId: $chunk->subagentRunId);
        }

        if (null !== $chunk->delta || null !== $chunk->rawEvent || ([] === $result && null !== $chunk->metadata)) {
            $result[] = new ReasoningMessageContentEvent($fields['id'], $chunk->delta ?? '', rawEvent: $chunk->rawEvent, metadata: $chunk->metadata, subagentRunId: $chunk->subagentRunId ?? $fields['owner']);
        }

        return $result;
    }

    /**
     * Which producer's lane a chunk belongs to.
     *
     * @param 'text'|'tool'|'reasoning' $kind
     */
    private function resolveLane(string $kind, ?string $entityId, ?string $tag, string $chunkType, string $entityKind): ?string
    {
        if (null !== $entityId) {
            foreach ($this->lanes as $pending) {
                if ($pending['kind'] === $kind && $pending['id'] === $entityId) {
                    $holder = self::ownerOf($pending);
                    if (null !== $tag && $tag !== $holder) {
                        throw new InvalidEventSequenceException(\sprintf("Cannot continue %s '%s': chunk subagentRunId '%s' does not match the open stream's subagent '%s'.", $entityKind, $entityId, $tag, $holder ?? '(the parent agent)'));
                    }

                    return $holder;
                }
            }

            return $tag;
        }

        if (null !== $tag) {
            return $tag;
        }

        if (($this->lanes[self::key(null)]['kind'] ?? null) === $kind) {
            return null;
        }

        $candidates = array_values(array_filter($this->lanes, static fn (array $pending) => $pending['kind'] === $kind));
        if (\count($candidates) > 1) {
            throw new InvalidEventSequenceException(\sprintf('Ambiguous %s: it carries neither a %s nor a subagentRunId, but %d lanes have an open %s. Attribute the chunk to the subagent it belongs to.', $chunkType, 'tool' === $kind ? 'toolCallId' : 'messageId', \count($candidates), $entityKind));
        }

        return 1 === \count($candidates) ? self::ownerOf($candidates[0]) : null;
    }

    /** @return list<Event> */
    private function closeLane(?string $owner): array
    {
        $pending = $this->lanes[self::key($owner)] ?? null;
        if (null === $pending) {
            return [];
        }
        unset($this->lanes[self::key($owner)]);

        return [match ($pending['kind']) {
            'text' => new TextMessageEndEvent($pending['id'], subagentRunId: $pending['owner']),
            'tool' => new ToolCallEndEvent($pending['id'], subagentRunId: $pending['owner']),
            'reasoning' => new ReasoningMessageEndEvent($pending['id'], subagentRunId: $pending['owner']),
        }];
    }

    /** @return list<Event> */
    private function closeAllLanes(): array
    {
        $events = [];
        foreach ($this->lanes as $key => $pending) {
            $events = [...$events, ...$this->closeLane(self::ownerFromKey($key))];
        }

        return $events;
    }

    private static function requireAgreement(string $entityKind, string $entityId, string $field, ?string $incoming, ?string $established): void
    {
        if (null !== $incoming && $incoming !== $established) {
            throw new InvalidEventSequenceException(\sprintf("Cannot continue %s '%s': chunk %s '%s' does not match the open stream's %s %s.", $entityKind, $entityId, $field, $incoming, $field, null === $established ? '(absent)' : "'{$established}'"));
        }
    }

    /**
     * Lane keys keep the parent agent apart from a subagent whose id is the empty string.
     */
    private static function key(?string $owner): string
    {
        return null === $owner ? 'parent' : 'subagent:'.$owner;
    }

    private static function ownerFromKey(string $key): ?string
    {
        return 'parent' === $key ? null : substr($key, \strlen('subagent:'));
    }

    /** @param array{owner: ?string, ...} $pending */
    private static function ownerOf(array $pending): ?string
    {
        return $pending['owner'];
    }
}
