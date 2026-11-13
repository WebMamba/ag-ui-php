<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Output format support. Enable structuredOutput when your agent can return responses
 * conforming to a JSON schema, which is useful for programmatic consumption.
 */
final class OutputCapabilities implements Model
{
    /**
     * @param bool|null $structuredOutput set true if the agent can produce structured JSON output matching a provided schema
     * @param list<string>|null $supportedMimeTypes MIME types the agent can produce (e.g., ["text/plain", "application/json"]). Omit if the agent only produces plain text.
     */
    public function __construct(
        public private(set) ?bool $structuredOutput = null,
        public private(set) ?array $supportedMimeTypes = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['structuredOutput', 'supportedMimeTypes'], $context, $path);

        return new self(
            structuredOutput: \array_key_exists('structuredOutput', $properties) && Json::present($properties['structuredOutput'], $context, Json::pointer($path, 'structuredOutput')) ? Json::bool($properties['structuredOutput'], Json::pointer($path, 'structuredOutput')) : null,
            supportedMimeTypes: \array_key_exists('supportedMimeTypes', $properties) && Json::present($properties['supportedMimeTypes'], $context, Json::pointer($path, 'supportedMimeTypes')) ? Json::list($properties['supportedMimeTypes'], Json::pointer($path, 'supportedMimeTypes'), static fn (mixed $item, string $at) => Json::string($item, $at)) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->structuredOutput) {
            $data['structuredOutput'] = $this->structuredOutput;
        }

        if (null !== $this->supportedMimeTypes) {
            $data['supportedMimeTypes'] = $this->supportedMimeTypes;
        }

        return $data;
    }
}
