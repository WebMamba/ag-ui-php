<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * What a tool returned, as a message in the conversation. Stands alone rather than composing
 * BaseMessage, because it carries no name.
 */
final class ToolMessage implements Message, Attributable
{
    /**
     * @param string $id identifies the message
     * @param string|list<\AgUi\Content\ContentPart> $content What the tool returned: either plain text, or an ordered list of parts. A tool returning structured data serialises it into text; media travel as parts of their own.
     * @param string $toolCallId the call this answers
     * @param string|null $error Why the tool failed, when it did. Present alongside content rather than instead of it, so a partial result survives a failure.
     * @param string|null $encryptedValue a provider's opaque artefact belonging to this message
     * @param array<array-key, mixed>|null $metadata extra information attached to this message
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $id,
        public private(set) string|array $content,
        public private(set) string $toolCallId,
        public private(set) ?string $error = null,
        public private(set) ?string $encryptedValue = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function role(): Role
    {
        return Role::Tool;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['role', 'id', 'content', 'toolCallId', 'error', 'encryptedValue', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'role', $path), 'tool', Json::pointer($path, 'role'));

        return new self(
            id: Json::string(Json::required($properties, 'id', $path), Json::pointer($path, 'id')),
            content: Json::stringOrList(Json::required($properties, 'content', $path), Json::pointer($path, 'content'), static fn (mixed $item, string $at) => \AgUi\Content\ContentParts::tryFromArray($item, $context, $at)),
            toolCallId: Json::string(Json::required($properties, 'toolCallId', $path), Json::pointer($path, 'toolCallId')),
            error: \array_key_exists('error', $properties) && Json::present($properties['error'], $context, Json::pointer($path, 'error')) ? Json::string($properties['error'], Json::pointer($path, 'error')) : null,
            encryptedValue: \array_key_exists('encryptedValue', $properties) && Json::present($properties['encryptedValue'], $context, Json::pointer($path, 'encryptedValue')) ? Json::string($properties['encryptedValue'], Json::pointer($path, 'encryptedValue')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'role' => 'tool',
            'id' => $this->id,
            'content' => \is_string($this->content) ? $this->content : array_map(static fn (\AgUi\Content\ContentPart $item) => Json::encodeObject($item->toArray()), $this->content),
            'toolCallId' => $this->toolCallId,
        ];

        if (null !== $this->error) {
            $data['error'] = $this->error;
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
