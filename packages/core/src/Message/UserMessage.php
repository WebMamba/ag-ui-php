<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * A message from the person using the application.
 */
final class UserMessage implements Message, Attributable
{
    /**
     * @param string|list<\AgUi\Content\ContentPart> $content what the person sent: either plain text, or an ordered list of parts for a multimodal message
     * @param string $id identifies the message within the conversation
     * @param string|null $name an optional display name for the author
     * @param string|null $encryptedValue a provider's opaque artefact belonging to this message, stored by a consumer and returned on a later turn
     * @param array<array-key, mixed>|null $metadata extra information attached to this message
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string|array $content,
        public private(set) string $id,
        public private(set) ?string $name = null,
        public private(set) ?string $encryptedValue = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function role(): Role
    {
        return Role::User;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['role', 'content', 'id', 'name', 'encryptedValue', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'role', $path), 'user', Json::pointer($path, 'role'));

        return new self(
            content: Json::stringOrList(Json::required($properties, 'content', $path), Json::pointer($path, 'content'), static fn (mixed $item, string $at) => \AgUi\Content\ContentParts::tryFromArray($item, $context, $at)),
            id: Json::string(Json::required($properties, 'id', $path), Json::pointer($path, 'id')),
            name: \array_key_exists('name', $properties) && Json::present($properties['name'], $context, Json::pointer($path, 'name')) ? Json::string($properties['name'], Json::pointer($path, 'name')) : null,
            encryptedValue: \array_key_exists('encryptedValue', $properties) && Json::present($properties['encryptedValue'], $context, Json::pointer($path, 'encryptedValue')) ? Json::string($properties['encryptedValue'], Json::pointer($path, 'encryptedValue')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'role' => 'user',
            'content' => \is_string($this->content) ? $this->content : array_map(static fn (\AgUi\Content\ContentPart $item) => Json::encodeObject($item->toArray()), $this->content),
            'id' => $this->id,
        ];

        if (null !== $this->name) {
            $data['name'] = $this->name;
        }

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
