<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Reasoning and thinking capabilities. Enable these when your agent exposes its internal
 * thought process (e.g., chain-of-thought, extended thinking).
 */
final class ReasoningCapabilities implements Model
{
    /**
     * @param bool|null $supported set true if the agent produces reasoning/thinking tokens visible to the client
     * @param bool|null $streaming Set true if reasoning tokens are streamed incrementally (vs. returned all at once).
     * @param bool|null $encrypted Set true if reasoning content is encrypted (zero-data-retention mode). Clients should expect opaque encryptedValue fields instead of readable content.
     */
    public function __construct(
        public private(set) ?bool $supported = null,
        public private(set) ?bool $streaming = null,
        public private(set) ?bool $encrypted = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['supported', 'streaming', 'encrypted'], $context, $path);

        return new self(
            supported: \array_key_exists('supported', $properties) && Json::present($properties['supported'], $context, Json::pointer($path, 'supported')) ? Json::bool($properties['supported'], Json::pointer($path, 'supported')) : null,
            streaming: \array_key_exists('streaming', $properties) && Json::present($properties['streaming'], $context, Json::pointer($path, 'streaming')) ? Json::bool($properties['streaming'], Json::pointer($path, 'streaming')) : null,
            encrypted: \array_key_exists('encrypted', $properties) && Json::present($properties['encrypted'], $context, Json::pointer($path, 'encrypted')) ? Json::bool($properties['encrypted'], Json::pointer($path, 'encrypted')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->supported) {
            $data['supported'] = $this->supported;
        }

        if (null !== $this->streaming) {
            $data['streaming'] = $this->streaming;
        }

        if (null !== $this->encrypted) {
            $data['encrypted'] = $this->encrypted;
        }

        return $data;
    }
}
