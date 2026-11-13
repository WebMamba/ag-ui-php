<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Patch;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Copies the value at from to path. RFC 6902 section 4.5.
 */
final class CopyOperation implements JsonPatchOperation
{
    /**
     * @param string $from where the value is copied from
     * @param string $path where the value is copied to
     * @param array<array-key, mixed> $extra members the protocol does not define, carried verbatim
     */
    public function __construct(
        #[Assert\Regex(pattern: '#^(/([^/~]|~[01])*)*$#')]
        public private(set) string $from,
        #[Assert\Regex(pattern: '#^(/([^/~]|~[01])*)*$#')]
        public private(set) string $path,
        public private(set) array $extra = [],
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::object($data, $path);
        Json::const(Json::required($properties, 'op', $path), 'copy', Json::pointer($path, 'op'));

        return new self(
            from: Json::string(Json::required($properties, 'from', $path), Json::pointer($path, 'from')),
            path: Json::string(Json::required($properties, 'path', $path), Json::pointer($path, 'path')),
            extra: array_map(Json::value(...), array_diff_key($properties, array_flip(['op', 'from', 'path']))),
        );
    }

    public function toArray(): array
    {
        $data = [
            'op' => 'copy',
            'from' => $this->from,
            'path' => $this->path,
        ];

        return $data + $this->extra;
    }
}
