<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * The name and arguments of a tool call.
 */
final class FunctionCall implements Model
{
    /**
     * @param string $name which tool is being called
     * @param string $arguments The arguments as a JSON string, not as parsed JSON. Kept as written because a model can emit arguments that are not valid JSON, and losing them at the protocol boundary would hide the fault from the consumer that has to handle it.
     */
    public function __construct(
        public private(set) string $name,
        public private(set) string $arguments,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['name', 'arguments'], $context, $path);

        return new self(
            name: Json::string(Json::required($properties, 'name', $path), Json::pointer($path, 'name')),
            arguments: Json::string(Json::required($properties, 'arguments', $path), Json::pointer($path, 'arguments')),
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];

        return $data;
    }
}
