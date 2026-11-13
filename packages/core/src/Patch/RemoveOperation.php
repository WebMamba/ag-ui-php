<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Patch;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Removes the value at path. RFC 6902 section 4.2.
 */
final class RemoveOperation implements JsonPatchOperation
{
    /**
     * @param string $path what to remove
     * @param array<array-key, mixed> $extra members the protocol does not define, carried verbatim
     */
    public function __construct(
        #[Assert\Regex(pattern: '#^(/([^/~]|~[01])*)*$#')]
        public private(set) string $path,
        public private(set) array $extra = [],
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::object($data, $path);
        Json::const(Json::required($properties, 'op', $path), 'remove', Json::pointer($path, 'op'));

        return new self(
            path: Json::string(Json::required($properties, 'path', $path), Json::pointer($path, 'path')),
            extra: array_map(Json::value(...), array_diff_key($properties, array_flip(['op', 'path']))),
        );
    }

    public function toArray(): array
    {
        $data = [
            'op' => 'remove',
            'path' => $this->path,
        ];

        return $data + $this->extra;
    }
}
