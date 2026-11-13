<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Closes a run that did not fail. Run-scoped, so it carries no subagent attribution.
 */
final class RunFinishedEvent implements Event
{
    /**
     * @param string $threadId the conversation this run belongs to
     * @param string $runId the run being closed
     * @param mixed $result The run's return value, if it has one. Any JSON value.
     * @param \AgUi\Run\RunFinishedOutcome|null $outcome Why the run ended. Absent means success, so every producer written before outcomes existed is already conformant.
     * @param list<\AgUi\Run\TokenUsage>|null $usage Token usage for the run, one entry per provider and model, so a run that invoked several models keeps them separate. A consumer that only wants totals sums across the entries. The run is the accounting boundary: usage covers every model call made within the run, calls made by its subagents included; an agent invoked as a separate run under parentRunId reports its own usage on its own terminal event; and a run that resumes an interrupted one reports only the calls it made itself, not the interrupted run's.
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     */
    public function __construct(
        public private(set) string $threadId,
        public private(set) string $runId,
        public private(set) mixed $result = null,
        #[Assert\Valid]
        public private(set) ?\AgUi\Run\RunFinishedOutcome $outcome = null,
        #[Assert\Valid]
        public private(set) ?array $usage = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::RunFinished;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'threadId', 'runId', 'result', 'outcome', 'usage', 'timestamp', 'rawEvent', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'RUN_FINISHED', Json::pointer($path, 'type'));

        return new self(
            threadId: Json::string(Json::required($properties, 'threadId', $path), Json::pointer($path, 'threadId')),
            runId: Json::string(Json::required($properties, 'runId', $path), Json::pointer($path, 'runId')),
            result: \array_key_exists('result', $properties) && Json::present($properties['result'], $context, Json::pointer($path, 'result')) ? Json::value(Json::forbidNull($properties['result'], Json::pointer($path, 'result'))) : null,
            outcome: \array_key_exists('outcome', $properties) && Json::present($properties['outcome'], $context, Json::pointer($path, 'outcome')) ? \AgUi\Run\RunFinishedOutcomes::tryFromArray($properties['outcome'], $context, Json::pointer($path, 'outcome')) : null,
            usage: \array_key_exists('usage', $properties) && Json::present($properties['usage'], $context, Json::pointer($path, 'usage')) ? Json::list($properties['usage'], Json::pointer($path, 'usage'), static fn (mixed $item, string $at) => \AgUi\Run\TokenUsage::fromArray(Json::object($item, $at), $context, $at)) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'RUN_FINISHED',
            'threadId' => $this->threadId,
            'runId' => $this->runId,
        ];

        if (null !== $this->result) {
            $data['result'] = $this->result;
        }

        if (null !== $this->outcome) {
            $data['outcome'] = Json::encodeObject($this->outcome->toArray());
        }

        if (null !== $this->usage) {
            $data['usage'] = array_map(static fn (\AgUi\Run\TokenUsage $item) => Json::encodeObject($item->toArray()), $this->usage);
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
