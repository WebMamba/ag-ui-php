<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Modalities the agent can produce as output. Clients use this to anticipate rich content in
 * the agent's response.
 */
final class MultimodalOutputCapabilities implements Model
{
    /**
     * @param bool|null $image set true if the agent can generate images as part of its response
     * @param bool|null $audio set true if the agent can produce audio output (text-to-speech, audio files)
     */
    public function __construct(
        public private(set) ?bool $image = null,
        public private(set) ?bool $audio = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['image', 'audio'], $context, $path);

        return new self(
            image: \array_key_exists('image', $properties) && Json::present($properties['image'], $context, Json::pointer($path, 'image')) ? Json::bool($properties['image'], Json::pointer($path, 'image')) : null,
            audio: \array_key_exists('audio', $properties) && Json::present($properties['audio'], $context, Json::pointer($path, 'audio')) ? Json::bool($properties['audio'], Json::pointer($path, 'audio')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->image) {
            $data['image'] = $this->image;
        }

        if (null !== $this->audio) {
            $data['audio'] = $this->audio;
        }

        return $data;
    }
}
