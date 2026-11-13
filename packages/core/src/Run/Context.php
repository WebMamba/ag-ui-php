<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * A named piece of ambient information given to the agent for the run, distinct from the
 * conversation.
 */
final class Context implements Model
{
    /**
     * @param string $description what this context is, for the agent to interpret
     * @param string $value the context itself
     */
    public function __construct(
        public private(set) string $description,
        public private(set) string $value,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['description', 'value'], $context, $path);

        return new self(
            description: Json::string(Json::required($properties, 'description', $path), Json::pointer($path, 'description')),
            value: Json::string(Json::required($properties, 'value', $path), Json::pointer($path, 'value')),
        );
    }

    public function toArray(): array
    {
        $data = [
            'description' => $this->description,
            'value' => $this->value,
        ];

        return $data;
    }
}
