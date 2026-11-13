<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Modalities the agent can accept as input. Clients use this to show or hide file upload
 * buttons, audio recorders, image pickers, etc.
 */
final class MultimodalInputCapabilities implements Model
{
    /**
     * @param bool|null $image Set true if the agent can process image inputs (e.g., screenshots, photos).
     * @param bool|null $audio set true if the agent can process audio inputs (speech, recordings)
     * @param bool|null $video set true if the agent can process video inputs
     * @param bool|null $pdf set true if the agent can process PDF documents
     * @param bool|null $file Set true if the agent can process arbitrary file uploads: files of a kind the image, audio, video and document parts do not cover. Says nothing about how a file arrives; a part's source (inline, URL or provider handle) is a separate question.
     */
    public function __construct(
        public private(set) ?bool $image = null,
        public private(set) ?bool $audio = null,
        public private(set) ?bool $video = null,
        public private(set) ?bool $pdf = null,
        public private(set) ?bool $file = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['image', 'audio', 'video', 'pdf', 'file'], $context, $path);

        return new self(
            image: \array_key_exists('image', $properties) && Json::present($properties['image'], $context, Json::pointer($path, 'image')) ? Json::bool($properties['image'], Json::pointer($path, 'image')) : null,
            audio: \array_key_exists('audio', $properties) && Json::present($properties['audio'], $context, Json::pointer($path, 'audio')) ? Json::bool($properties['audio'], Json::pointer($path, 'audio')) : null,
            video: \array_key_exists('video', $properties) && Json::present($properties['video'], $context, Json::pointer($path, 'video')) ? Json::bool($properties['video'], Json::pointer($path, 'video')) : null,
            pdf: \array_key_exists('pdf', $properties) && Json::present($properties['pdf'], $context, Json::pointer($path, 'pdf')) ? Json::bool($properties['pdf'], Json::pointer($path, 'pdf')) : null,
            file: \array_key_exists('file', $properties) && Json::present($properties['file'], $context, Json::pointer($path, 'file')) ? Json::bool($properties['file'], Json::pointer($path, 'file')) : null,
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

        if (null !== $this->video) {
            $data['video'] = $this->video;
        }

        if (null !== $this->pdf) {
            $data['pdf'] = $this->pdf;
        }

        if (null !== $this->file) {
            $data['file'] = $this->file;
        }

        return $data;
    }
}
