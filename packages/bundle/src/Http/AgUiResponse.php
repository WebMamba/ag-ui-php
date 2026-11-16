<?php

declare(strict_types=1);

namespace AgUi\Bundle\Http;

use AgUi\Event\Event;
use AgUi\Serializer\AgUiSerializer;
use Symfony\Component\HttpFoundation\EventStreamResponse;
use Symfony\Component\HttpFoundation\ServerEvent;
use Symfony\Component\Serializer\Encoder\JsonEncode;

/**
 * An AG-UI event stream as a response: one Server-Sent Event per AG-UI event, flushed as
 * it is produced, and no longer produced once the client is gone.
 *
 *     return new AgUiResponse($agUi->run($input, $navigator));
 */
final class AgUiResponse extends EventStreamResponse
{
    /**
     * @param iterable<Event> $events
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(iterable $events, int $status = 200, array $headers = [])
    {
        parent::__construct(static function () use ($events): \Generator {
            $serializer = AgUiSerializer::create();

            foreach ($events as $event) {
                yield new ServerEvent($serializer->serialize($event, 'json', [JsonEncode::OPTIONS => AgUiSerializer::JSON_ENCODE_OPTIONS]));
            }
        }, $status, $headers);
    }
}
