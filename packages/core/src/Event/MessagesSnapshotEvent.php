<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The complete set of messages the producer owns, in order. Conversation-wide rather than a
 * plain overwrite: a consumer may keep messages of its own that no producer tracks, so exactly
 * how a snapshot reconciles with those is behavioural and belongs in the prose. Being
 * conversation-wide it cannot belong to a single subagent, so it carries no attribution; it
 * does establish which subagent owns each message it contains, through the messages
 * themselves.
 */
final class MessagesSnapshotEvent implements Event
{
    /**
     * @param list<\AgUi\Message\Message> $messages the messages the producer is declaring, in order
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     */
    public function __construct(
        #[Assert\Valid]
        public private(set) array $messages,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::MessagesSnapshot;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'messages', 'timestamp', 'rawEvent', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'MESSAGES_SNAPSHOT', Json::pointer($path, 'type'));

        return new self(
            messages: array_values(array_filter(Json::list(Json::required($properties, 'messages', $path), Json::pointer($path, 'messages'), static fn (mixed $item, string $at) => \AgUi\Message\Messages::tryFromArray($item, $context, $at)))),
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'MESSAGES_SNAPSHOT',
            'messages' => array_map(static fn (\AgUi\Message\Message $item) => Json::encodeObject($item->toArray()), $this->messages),
        ];

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
