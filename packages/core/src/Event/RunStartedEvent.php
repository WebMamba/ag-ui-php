<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Opens a run. Run-scoped, so it carries no subagent attribution.
 */
final class RunStartedEvent implements Event
{
    /**
     * @param string $threadId the conversation this run belongs to
     * @param string $runId identifies this run
     * @param string|null $protocolVersion The protocol version this producer speaks, such as "1.0" — the producer's own version, not an echo of the input's, which is what makes the pair a negotiation: each side declares itself and the consumer sees a downgrade the moment it happens. Absent means a producer from before the protocol carried a version.
     * @param string|null $parentRunId the run that spawned this one, when an agent invokes another agent as a separate run rather than as a subagent within one
     * @param \AgUi\Run\RunAgentInput|null $input the request this run was started from, echoed back so a consumer that did not make the request can still see what the agent was asked
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     */
    public function __construct(
        public private(set) string $threadId,
        public private(set) string $runId,
        public private(set) ?string $protocolVersion = null,
        public private(set) ?string $parentRunId = null,
        #[Assert\Valid]
        public private(set) ?\AgUi\Run\RunAgentInput $input = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::RunStarted;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'threadId', 'runId', 'protocolVersion', 'parentRunId', 'input', 'timestamp', 'rawEvent', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'RUN_STARTED', Json::pointer($path, 'type'));

        return new self(
            threadId: Json::string(Json::required($properties, 'threadId', $path), Json::pointer($path, 'threadId')),
            runId: Json::string(Json::required($properties, 'runId', $path), Json::pointer($path, 'runId')),
            protocolVersion: \array_key_exists('protocolVersion', $properties) && Json::present($properties['protocolVersion'], $context, Json::pointer($path, 'protocolVersion')) ? Json::string($properties['protocolVersion'], Json::pointer($path, 'protocolVersion')) : null,
            parentRunId: \array_key_exists('parentRunId', $properties) && Json::present($properties['parentRunId'], $context, Json::pointer($path, 'parentRunId')) ? Json::string($properties['parentRunId'], Json::pointer($path, 'parentRunId')) : null,
            input: \array_key_exists('input', $properties) && Json::present($properties['input'], $context, Json::pointer($path, 'input')) ? \AgUi\Run\RunAgentInput::fromArray(Json::object($properties['input'], Json::pointer($path, 'input')), $context, Json::pointer($path, 'input')) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'RUN_STARTED',
            'threadId' => $this->threadId,
            'runId' => $this->runId,
        ];

        if (null !== $this->protocolVersion) {
            $data['protocolVersion'] = $this->protocolVersion;
        }

        if (null !== $this->parentRunId) {
            $data['parentRunId'] = $this->parentRunId;
        }

        if (null !== $this->input) {
            $data['input'] = Json::encodeObject($this->input->toArray());
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
