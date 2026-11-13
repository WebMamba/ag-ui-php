<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Opens a streamed text message. The content arrives as TEXT_MESSAGE_CONTENT events and the
 * message closes with TEXT_MESSAGE_END.
 */
final class TextMessageStartEvent implements Event, Attributable
{
    /**
     * @param string $messageId identifies the message this stream builds, and ties the later content and end events to it
     * @param \AgUi\Message\TextMessageRole|null $role Who the message is from. An absent role means assistant; that meaning is normative and stated in the prose, because a validator treats a default as documentation rather than as behaviour.
     * @param string|null $name an optional display name for the author, for providers that distinguish several participants in one role
     * @param int|null $timestamp When the event was created. Bounded to the range JSON numbers survive a round trip in, so the value a consumer reads is the value the producer wrote. Deliberately not a float. The unit is not constrained here, because it never has been stated normatively; every SDK that sets it in practice uses milliseconds since the Unix epoch, and a producer choosing another unit will be misread by consumers even though it validates. Nothing in the protocol computes with this value.
     * @param mixed $rawEvent The provider-native event this one was translated from, carried verbatim for debugging and for consumers that need detail the protocol does not model. Any JSON value.
     * @param array<array-key, mixed>|null $metadata extra information attached to this event
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $messageId,
        public private(set) ?\AgUi\Message\TextMessageRole $role = null,
        public private(set) ?string $name = null,
        #[Assert\Range(min: -9007199254740991, max: 9007199254740991)]
        public private(set) ?int $timestamp = null,
        public private(set) mixed $rawEvent = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function type(): EventType
    {
        return EventType::TextMessageStart;
    }

    public function withTimestamp(int $timestamp): static
    {
        $event = clone $this;
        $event->timestamp = $timestamp;

        return $event;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'messageId', 'role', 'name', 'timestamp', 'rawEvent', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'TEXT_MESSAGE_START', Json::pointer($path, 'type'));

        return new self(
            messageId: Json::string(Json::required($properties, 'messageId', $path), Json::pointer($path, 'messageId')),
            role: \array_key_exists('role', $properties) && Json::present($properties['role'], $context, Json::pointer($path, 'role')) ? Json::enum($properties['role'], \AgUi\Message\TextMessageRole::class, Json::pointer($path, 'role')) : null,
            name: \array_key_exists('name', $properties) && Json::present($properties['name'], $context, Json::pointer($path, 'name')) ? Json::string($properties['name'], Json::pointer($path, 'name')) : null,
            timestamp: \array_key_exists('timestamp', $properties) && Json::present($properties['timestamp'], $context, Json::pointer($path, 'timestamp')) ? Json::int($properties['timestamp'], Json::pointer($path, 'timestamp')) : null,
            rawEvent: \array_key_exists('rawEvent', $properties) && Json::present($properties['rawEvent'], $context, Json::pointer($path, 'rawEvent')) ? Json::value(Json::forbidNull($properties['rawEvent'], Json::pointer($path, 'rawEvent'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'TEXT_MESSAGE_START',
            'messageId' => $this->messageId,
        ];

        if (null !== $this->role) {
            $data['role'] = $this->role->value;
        }

        if (null !== $this->name) {
            $data['name'] = $this->name;
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
