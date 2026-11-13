<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Multimodal input and output support. Organized into input and output sub-objects so clients
 * can independently query what the agent accepts versus what it produces.
 */
final class MultimodalCapabilities implements Model
{
    /**
     * @param MultimodalInputCapabilities|null $input modalities the agent can accept as input (images, audio, video, PDFs, files)
     * @param MultimodalOutputCapabilities|null $output modalities the agent can produce as output (images, audio)
     */
    public function __construct(
        #[Assert\Valid]
        public private(set) ?MultimodalInputCapabilities $input = null,
        #[Assert\Valid]
        public private(set) ?MultimodalOutputCapabilities $output = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['input', 'output'], $context, $path);

        return new self(
            input: \array_key_exists('input', $properties) && Json::present($properties['input'], $context, Json::pointer($path, 'input')) ? MultimodalInputCapabilities::fromArray(Json::object($properties['input'], Json::pointer($path, 'input')), $context, Json::pointer($path, 'input')) : null,
            output: \array_key_exists('output', $properties) && Json::present($properties['output'], $context, Json::pointer($path, 'output')) ? MultimodalOutputCapabilities::fromArray(Json::object($properties['output'], Json::pointer($path, 'output')), $context, Json::pointer($path, 'output')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->input) {
            $data['input'] = Json::encodeObject($this->input->toArray());
        }

        if (null !== $this->output) {
            $data['output'] = Json::encodeObject($this->output->toArray());
        }

        return $data;
    }
}
