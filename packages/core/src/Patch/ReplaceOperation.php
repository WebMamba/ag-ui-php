<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Patch;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Replaces the value at path. RFC 6902 section 4.3.
 */
final class ReplaceOperation implements JsonPatchOperation
{
    /**
     * @param string $path what to replace
     * @param mixed $value The replacement. Any JSON value, including null.
     * @param array<array-key, mixed> $extra members the protocol does not define, carried verbatim
     */
    public function __construct(
        #[Assert\Regex(pattern: '#^(/([^/~]|~[01])*)*$#')]
        public private(set) string $path,
        public private(set) mixed $value,
        public private(set) array $extra = [],
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::object($data, $path);
        Json::const(Json::required($properties, 'op', $path), 'replace', Json::pointer($path, 'op'));

        return new self(
            path: Json::string(Json::required($properties, 'path', $path), Json::pointer($path, 'path')),
            value: Json::value(Json::required($properties, 'value', $path)),
            extra: array_map(Json::value(...), array_diff_key($properties, array_flip(['op', 'path', 'value']))),
        );
    }

    public function toArray(): array
    {
        $data = [
            'op' => 'replace',
            'path' => $this->path,
            'value' => $this->value,
        ];

        return $data + $this->extra;
    }
}
