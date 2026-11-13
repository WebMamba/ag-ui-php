<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * Bytes carried inline.
 */
final class DataSource implements PartSource
{
    /**
     * @param string $value The bytes, base64-encoded. contentEncoding is an annotation rather than a constraint in 2020-12, so a malformed string still validates here; rejecting one is the decoder's job.
     * @param string $mimeType What the bytes are. Required here, unlike on a URL source, because nothing else can tell a consumer how to read them.
     */
    public function __construct(
        public private(set) string $value,
        public private(set) string $mimeType,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'value', 'mimeType'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'data', Json::pointer($path, 'type'));

        return new self(
            value: Json::string(Json::required($properties, 'value', $path), Json::pointer($path, 'value')),
            mimeType: Json::string(Json::required($properties, 'mimeType', $path), Json::pointer($path, 'mimeType')),
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'data',
            'value' => $this->value,
            'mimeType' => $this->mimeType,
        ];

        return $data;
    }
}
