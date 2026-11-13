<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A call an assistant message made. Carries no subagent attribution of its own and inherits
 * its containing message's, since several calls can share one parent.
 */
final class ToolCall implements Model
{
    /**
     * @param string $id Identifies the call. The answering tool message carries this as its toolCallId.
     * @param FunctionCall $function what is being called, and with what
     * @param string|null $encryptedValue a provider's opaque artefact belonging to this call
     * @param array<array-key, mixed>|null $metadata Extra information attached to this call. Carried here rather than folded into the containing message, because several calls can share one parent and merging them would make the result depend on their order.
     */
    public function __construct(
        public private(set) string $id,
        #[Assert\Valid]
        public private(set) FunctionCall $function,
        public private(set) ?string $encryptedValue = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'id', 'function', 'encryptedValue', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'function', Json::pointer($path, 'type'));

        return new self(
            id: Json::string(Json::required($properties, 'id', $path), Json::pointer($path, 'id')),
            function: FunctionCall::fromArray(Json::object(Json::required($properties, 'function', $path), Json::pointer($path, 'function')), $context, Json::pointer($path, 'function')),
            encryptedValue: \array_key_exists('encryptedValue', $properties) && Json::present($properties['encryptedValue'], $context, Json::pointer($path, 'encryptedValue')) ? Json::string($properties['encryptedValue'], Json::pointer($path, 'encryptedValue')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'function',
            'id' => $this->id,
            'function' => Json::encodeObject($this->function->toArray()),
        ];

        if (null !== $this->encryptedValue) {
            $data['encryptedValue'] = $this->encryptedValue;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        return $data;
    }
}
