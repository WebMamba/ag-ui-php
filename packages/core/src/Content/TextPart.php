<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * A text part.
 */
final class TextPart implements ContentPart
{
    /**
     * @param string $text the text
     * @param string|null $id Identifies this part within its message. Optional, and nothing reads it yet: reserved so that a streamed part can be matched to its entry in history once assistant messages carry parts too.
     * @param mixed $metadata Extra information about this part. Unconstrained, as on the media parts. This is where a text search hit carries its source and title, rather than the protocol modelling a search-result part of its own.
     */
    public function __construct(
        public private(set) string $text,
        public private(set) ?string $id = null,
        public private(set) mixed $metadata = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'text', 'id', 'metadata'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'text', Json::pointer($path, 'type'));

        return new self(
            text: Json::string(Json::required($properties, 'text', $path), Json::pointer($path, 'text')),
            id: \array_key_exists('id', $properties) && Json::present($properties['id'], $context, Json::pointer($path, 'id')) ? Json::string($properties['id'], Json::pointer($path, 'id')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::value(Json::forbidNull($properties['metadata'], Json::pointer($path, 'metadata'))) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'text',
            'text' => $this->text,
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
