<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * A tool the agent may call.
 */
final class Tool implements Model
{
    /**
     * @param string $name the tool's name, as the agent will call it
     * @param string $description what the tool does, for the agent to decide when to use it
     * @param mixed $parameters A JSON Schema describing the tool's arguments. Carried opaquely: the protocol does not constrain or validate it. Optional, because all three SDKs already treat it that way and a tool that takes no arguments has nothing to declare; an absent schema and an empty one mean the same thing to an agent.
     * @param array<array-key, mixed>|null $metadata extra information about the tool, for consumers that attach their own rendering or routing information to it
     */
    public function __construct(
        public private(set) string $name,
        public private(set) string $description,
        public private(set) mixed $parameters = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['name', 'description', 'parameters', 'metadata'], $context, $path);

        return new self(
            name: Json::string(Json::required($properties, 'name', $path), Json::pointer($path, 'name')),
            description: Json::string(Json::required($properties, 'description', $path), Json::pointer($path, 'description')),
            parameters: \array_key_exists('parameters', $properties) && Json::present($properties['parameters'], $context, Json::pointer($path, 'parameters')) ? Json::value(Json::forbidNull($properties['parameters'], Json::pointer($path, 'parameters'))) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
        ];

        if (null !== $this->parameters) {
            $data['parameters'] = $this->parameters;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        return $data;
    }
}
