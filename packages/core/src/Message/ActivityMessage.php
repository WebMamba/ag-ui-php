<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * Structured progress that is not conversation content, materialised as a message so it keeps
 * its place in the sequence. Stands alone rather than composing BaseMessage, because its
 * content is an object rather than a string.
 */
final class ActivityMessage implements Message, Attributable
{
    /**
     * @param string $id identifies the message
     * @param string $activityType What kind of activity this is. An open string: the set is the producer's.
     * @param array<array-key, mixed> $content the activity's payload, open by key
     * @param array<array-key, mixed>|null $metadata extra information attached to this message
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $id,
        public private(set) string $activityType,
        public private(set) array $content,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public function role(): Role
    {
        return Role::Activity;
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['role', 'id', 'activityType', 'content', 'metadata', 'subagentRunId'], $context, $path);
        Json::const(Json::required($properties, 'role', $path), 'activity', Json::pointer($path, 'role'));

        return new self(
            id: Json::string(Json::required($properties, 'id', $path), Json::pointer($path, 'id')),
            activityType: Json::string(Json::required($properties, 'activityType', $path), Json::pointer($path, 'activityType')),
            content: Json::openObject(Json::required($properties, 'content', $path), Json::pointer($path, 'content')),
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'role' => 'activity',
            'id' => $this->id,
            'activityType' => $this->activityType,
            'content' => Json::encodeObject($this->content),
        ];

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        if (null !== $this->subagentRunId) {
            $data['subagentRunId'] = $this->subagentRunId;
        }

        return $data;
    }
}
