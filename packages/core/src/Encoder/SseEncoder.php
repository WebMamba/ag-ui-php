<?php

declare(strict_types=1);

namespace AgUi\Encoder;

use AgUi\Event\Event;
use AgUi\Protocol;
use AgUi\Serializer\AgUiSerializer;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Encodes events as Server-Sent Events: one "data:" line of JSON per event, each
 * frame ending with a blank line.
 */
final class SseEncoder
{
    private SerializerInterface $serializer;

    public function __construct(?SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer ?? AgUiSerializer::create();
    }

    public function encode(Event $event): string
    {
        return 'data: '.$this->serializer->serialize($event, 'json', [JsonEncode::OPTIONS => AgUiSerializer::JSON_ENCODE_OPTIONS])."\n\n";
    }

    /**
     * Always text/event-stream: whatever the client's Accept header says, this is the
     * only encoding written. (The protocol's protobuf encoding is not supported.).
     */
    public function contentType(): string
    {
        return Protocol::SSE_MEDIA_TYPE;
    }
}
