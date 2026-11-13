<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * Bytes referenced by URL, fetched by whoever needs them.
 */
final class UrlSource implements PartSource
{
    /**
     * @param string $value The URL. Deliberately not constrained to a URI format, so a scheme a producer already uses is not rejected here.
     * @param string|null $mimeType What the resource is, when the producer knows. Optional, because the response can say.
     */
    public function __construct(
        public private(set) string $value,
        public private(set) ?string $mimeType = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'value', 'mimeType'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'url', Json::pointer($path, 'type'));

        return new self(
            value: Json::string(Json::required($properties, 'value', $path), Json::pointer($path, 'value')),
            mimeType: \array_key_exists('mimeType', $properties) && Json::present($properties['mimeType'], $context, Json::pointer($path, 'mimeType')) ? Json::string($properties['mimeType'], Json::pointer($path, 'mimeType')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'url',
            'value' => $this->value,
        ];

        if (null !== $this->mimeType) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }
}
