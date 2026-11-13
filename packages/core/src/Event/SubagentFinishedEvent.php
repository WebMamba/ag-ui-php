<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ends a subagent invocation's segment of this run, either because the work completed or
 * because it is suspended awaiting outside input.
 */
final class SubagentFinishedEvent implements Event
{
    /**
     * @param string $subagentRunId the invocation being closed
     * @param mixed $result The subagent's return value, if it has one. Any JSON value, mirroring RUN_FINISHED.result.
     * @param \AgUi\Run\SubagentFinishedOutcome|null $outcome Why the segment ended. Absent means success. A suspended subagent is neither a success nor a failure, which is why saying so needs its own value rather than being inferred from a later interrupt.
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     */
    public function __construct(
        public private(set) string $subagentRunId,
        public private(set) mixed $result = null,
        #[Assert\Valid]
        public private(set) ?\AgUi\Run\SubagentFinishedOutcome $outcome = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::SubagentFinished;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'subagentRunId', 'result', 'outcome', 'timestamp', 'rawEvent', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'SUBAGENT_FINISHED', Json::pointer($path, 'type'));

        return new self(
            subagentRunId: Json::string(Json::required($properties, 'subagentRunId', $path), Json::pointer($path, 'subagentRunId')),
            result: \array_key_exists('result', $properties) && Json::present($properties['result'], $context, Json::pointer($path, 'result')) ? Json::value(Json::forbidNull($properties['result'], Json::pointer($path, 'result'))) : null,
            outcome: \array_key_exists('outcome', $properties) && Json::present($properties['outcome'], $context, Json::pointer($path, 'outcome')) ? \AgUi\Run\SubagentFinishedOutcomes::tryFromArray($properties['outcome'], $context, Json::pointer($path, 'outcome')) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'SUBAGENT_FINISHED',
            'subagentRunId' => $this->subagentRunId,
        ];

        if (null !== $this->result) {
            $data['result'] = $this->result;
        }

        if (null !== $this->outcome) {
            $data['outcome'] = Json::encodeObject($this->outcome->toArray());
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
