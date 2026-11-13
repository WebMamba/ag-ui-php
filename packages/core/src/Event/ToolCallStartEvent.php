<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Opens a tool call. The arguments arrive as TOOL_CALL_ARGS events and the call closes with
 * TOOL_CALL_END.
 */
final class ToolCallStartEvent implements Event, Attributable
{
    /**
     * @param string $toolCallId identifies the call, and ties the later args, end and result events to it
     * @param string $toolCallName which tool is being called
     * @param string|null $parentMessageId The assistant message that holds this call. Absent means the producer did not attribute it to one.
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $toolCallId,
        public private(set) string $toolCallName,
        public private(set) ?string $parentMessageId = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::ToolCallStart;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'toolCallId', 'toolCallName', 'parentMessageId', 'timestamp', 'rawEvent', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'TOOL_CALL_START', Json::pointer($path, 'type'));

        return new self(
            toolCallId: Json::string(Json::required($properties, 'toolCallId', $path), Json::pointer($path, 'toolCallId')),
            toolCallName: Json::string(Json::required($properties, 'toolCallName', $path), Json::pointer($path, 'toolCallName')),
            parentMessageId: \array_key_exists('parentMessageId', $properties) && Json::present($properties['parentMessageId'], $context, Json::pointer($path, 'parentMessageId')) ? Json::string($properties['parentMessageId'], Json::pointer($path, 'parentMessageId')) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'TOOL_CALL_START',
            'toolCallId' => $this->toolCallId,
            'toolCallName' => $this->toolCallName,
        ];

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

        if (null !== $this->subagentRunId) {
            $data['subagentRunId'] = $this->subagentRunId;
        }

        return $data;
    }
}
