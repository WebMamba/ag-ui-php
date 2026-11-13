<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Announces that a subagent invocation has begun. Everything the subagent produces afterwards
 * is attributed by carrying its subagentRunId, so a consumer can group the work without
 * replaying the stream. Composed from BaseEvent alone rather than Attributable, because here
 * subagentRunId identifies the subagent rather than attributing the event to one; attribution
 * to an enclosing subagent is parentSubagentRunId.
 */
final class SubagentStartedEvent implements Event
{
    /**
     * @param string $subagentRunId the invocation being announced
     * @param string $name the subagent's name, which is reusable across invocations, unlike subagentRunId
     * @param string|null $description what this subagent is for, for a consumer to display
     * @param string|null $parentSubagentRunId The subagent invocation that spawned this one, for nested delegation. Absent means the parent agent spawned it directly.
     * @param string|null $parentToolCallId The tool call that spawned this subagent, for the pattern where agents are exposed to a model as tools. Lets a consumer tie the subagent to the call without reading rawEvent.
     * @param string|null $parentMessageId the message that held the spawning tool call
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     */
    public function __construct(
        public private(set) string $subagentRunId,
        public private(set) string $name,
        public private(set) ?string $description = null,
        public private(set) ?string $parentSubagentRunId = null,
        public private(set) ?string $parentToolCallId = null,
        public private(set) ?string $parentMessageId = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::SubagentStarted;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'subagentRunId', 'name', 'description', 'parentSubagentRunId', 'parentToolCallId', 'parentMessageId', 'timestamp', 'rawEvent', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'SUBAGENT_STARTED', Json::pointer($path, 'type'));

        return new self(
            subagentRunId: Json::string(Json::required($properties, 'subagentRunId', $path), Json::pointer($path, 'subagentRunId')),
            name: Json::string(Json::required($properties, 'name', $path), Json::pointer($path, 'name')),
            description: \array_key_exists('description', $properties) && Json::present($properties['description'], $context, Json::pointer($path, 'description')) ? Json::string($properties['description'], Json::pointer($path, 'description')) : null,
            parentSubagentRunId: \array_key_exists('parentSubagentRunId', $properties) && Json::present($properties['parentSubagentRunId'], $context, Json::pointer($path, 'parentSubagentRunId')) ? Json::string($properties['parentSubagentRunId'], Json::pointer($path, 'parentSubagentRunId')) : null,
            parentToolCallId: \array_key_exists('parentToolCallId', $properties) && Json::present($properties['parentToolCallId'], $context, Json::pointer($path, 'parentToolCallId')) ? Json::string($properties['parentToolCallId'], Json::pointer($path, 'parentToolCallId')) : null,
            parentMessageId: \array_key_exists('parentMessageId', $properties) && Json::present($properties['parentMessageId'], $context, Json::pointer($path, 'parentMessageId')) ? Json::string($properties['parentMessageId'], Json::pointer($path, 'parentMessageId')) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'SUBAGENT_STARTED',
            'subagentRunId' => $this->subagentRunId,
            'name' => $this->name,
        ];

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if (null !== $this->parentSubagentRunId) {
            $data['parentSubagentRunId'] = $this->parentSubagentRunId;
        }

        if (null !== $this->parentToolCallId) {
            $data['parentToolCallId'] = $this->parentToolCallId;
        }

        if (null !== $this->parentMessageId) {
            $data['parentMessageId'] = $this->parentMessageId;
        }

        if (null !== $this->timestamp) {
            $data['timestamp'] = $this->timestamp;
        }

        if (null !== $this->rawEvent) {
            $data['rawEvent'] = $this->rawEvent;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        return $data;
    }
}
