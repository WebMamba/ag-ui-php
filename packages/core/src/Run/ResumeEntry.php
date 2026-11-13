<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * An answer to one interrupt, sent on the run that continues from it.
 */
final class ResumeEntry implements Model
{
    /**
     * @param string $interruptId the interrupt being answered
     * @param ResumeStatus $status whether the interrupt was answered or abandoned
     * @param mixed $payload The answer the agent asked for and will act on. Any JSON value.
     * @param array<array-key, mixed>|null $metadata envelope information about the response, such as signatures or routing keys, as opposed to payload, which is the answer itself
     */
    public function __construct(
        public private(set) string $interruptId,
        public private(set) ResumeStatus $status,
        public private(set) mixed $payload = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['interruptId', 'status', 'payload', 'metadata'], $context, $path);

        return new self(
            interruptId: Json::string(Json::required($properties, 'interruptId', $path), Json::pointer($path, 'interruptId')),
            status: Json::enum(Json::required($properties, 'status', $path), ResumeStatus::class, Json::pointer($path, 'status')),
            payload: \array_key_exists('payload', $properties) && Json::present($properties['payload'], $context, Json::pointer($path, 'payload')) ? Json::value(Json::forbidNull($properties['payload'], Json::pointer($path, 'payload'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'interruptId' => $this->interruptId,
            'status' => $this->status->value,
        ];

        if (null !== $this->payload) {
            $data['payload'] = $this->payload;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        return $data;
    }
}
