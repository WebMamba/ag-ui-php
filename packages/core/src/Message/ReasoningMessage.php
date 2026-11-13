<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * A span of the agent's reasoning, materialised as a message. Stands alone rather than
 * composing BaseMessage, because it carries no name.
 */
final class ReasoningMessage implements Message, Attributable
{
    /**
     * @param string $id identifies the message
     * @param string $content the reasoning text
     * @param string|null $encryptedValue a provider's opaque reasoning artefact belonging to this message
     * @param array<array-key, mixed>|null $metadata extra information attached to this message
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $id,
        public private(set) string $content,
        public private(set) ?string $encryptedValue = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function role(): Role
    {
        return Role::Reasoning;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['role', 'id', 'content', 'encryptedValue', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'role', $path), 'reasoning', Json::pointer($path, 'role'));

        return new self(
            id: Json::string(Json::required($properties, 'id', $path), Json::pointer($path, 'id')),
            content: Json::string(Json::required($properties, 'content', $path), Json::pointer($path, 'content')),
            encryptedValue: \array_key_exists('encryptedValue', $properties) && Json::present($properties['encryptedValue'], $context, Json::pointer($path, 'encryptedValue')) ? Json::string($properties['encryptedValue'], Json::pointer($path, 'encryptedValue')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'role' => 'reasoning',
            'id' => $this->id,
            'content' => $this->content,
        ];

        if (null !== $this->encryptedValue) {
            $data['encryptedValue'] = $this->encryptedValue;
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
