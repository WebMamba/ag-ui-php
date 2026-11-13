<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Describes a subagent that can be invoked by a parent agent.
 */
final class SubagentInfo implements Model
{
    /**
     * @param string $name unique name or identifier of the subagent
     * @param string|null $description What this subagent specializes in. Helps clients build agent selection UIs.
     */
    public function __construct(
        public private(set) string $name,
        public private(set) ?string $description = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['name', 'description'], $context, $path);

        return new self(
            name: Json::string(Json::required($properties, 'name', $path), Json::pointer($path, 'name')),
            description: \array_key_exists('description', $properties) && Json::present($properties['description'], $context, Json::pointer($path, 'description')) ? Json::string($properties['description'], Json::pointer($path, 'description')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
        ];

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
