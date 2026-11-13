<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An image part.
 */
final class ImagePart implements ContentPart
{
    /**
     * @param PartSource $source where the image comes from
     * @param string|null $id Identifies this part within its message. Optional, and nothing reads it yet: reserved as on the text part.
     * @param mixed $metadata Extra information about this part. Unconstrained rather than an object: inherited from the SDKs, which declare it unknown rather than a record; listed under known divergences in the README rather than resolved here.
     */
    public function __construct(
        #[Assert\Valid]
        public private(set) PartSource $source,
        public private(set) ?string $id = null,
        public private(set) mixed $metadata = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'source', 'id', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'image', Json::pointer($path, 'type'));

        return new self(
            source: PartSources::fromArray(Json::required($properties, 'source', $path), $context, Json::pointer($path, 'source')),
            id: \array_key_exists('id', $properties) && Json::present($properties['id'], $context, Json::pointer($path, 'id')) ? Json::string($properties['id'], Json::pointer($path, 'id')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::value(Json::forbidNull($properties['metadata'], Json::pointer($path, 'metadata'))) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'image',
            'source' => Json::encodeObject($this->source->toArray()),
        ];

        if (null !== $this->id) {
            $data['id'] = $this->id;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
