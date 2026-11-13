<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reports structured progress that is not conversation content, such as a step a UI renders as
 * its own widget.
 */
final class ActivitySnapshotEvent implements Event, Attributable
{
    /**
     * @param string $messageId the activity message this describes
     * @param string $activityType What kind of activity this is. An open string: the set is the producer's, not the protocol's.
     * @param array<array-key, mixed> $content the activity's payload, open by key
     * @param bool|null $replace Whether this snapshot overwrites the activity's existing content. Absent means it does, and that meaning is normative; only an explicit false asks a consumer to leave what is already there. It does not ask for a merge — ACTIVITY_DELTA is how content is changed incrementally. What a consumer does with a non-overwriting snapshot is behavioural and belongs in the prose.
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $messageId,
        public private(set) string $activityType,
        public private(set) array $content,
        public private(set) ?bool $replace = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::ActivitySnapshot;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'messageId', 'activityType', 'content', 'replace', 'timestamp', 'rawEvent', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'ACTIVITY_SNAPSHOT', Json::pointer($path, 'type'));

        return new self(
            messageId: Json::string(Json::required($properties, 'messageId', $path), Json::pointer($path, 'messageId')),
            activityType: Json::string(Json::required($properties, 'activityType', $path), Json::pointer($path, 'activityType')),
            content: Json::openObject(Json::required($properties, 'content', $path), Json::pointer($path, 'content')),
            replace: \array_key_exists('replace', $properties) && Json::present($properties['replace'], $context, Json::pointer($path, 'replace')) ? Json::bool($properties['replace'], Json::pointer($path, 'replace')) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'ACTIVITY_SNAPSHOT',
            'messageId' => $this->messageId,
            'activityType' => $this->activityType,
            'content' => Json::encodeObject($this->content),
        ];

        if (null !== $this->replace) {
            $data['replace'] = $this->replace;
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
