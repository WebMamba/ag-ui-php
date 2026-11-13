<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * Bytes already at the provider, named by a handle the provider issued: an OpenAI or Anthropic
 * file id, a Gemini file URI, a storage URL only that provider can read. No bytes travel and
 * nothing is fetched. Only the provider that minted the handle can resolve it; a peer that
 * cannot drops the part as it drops any part it cannot use.
 */
final class FileSource implements PartSource
{
    /**
     * @param string $value The handle, exactly as the provider issued it. Opaque: a consumer MUST NOT fetch it, parse it or read a scheme out of it.
     * @param string|null $provider Who issued the handle, when the producer knows. Optional: an agent already knows which provider it talks to. When present, SHOULD be the lowercase vendor id (openai, anthropic, google) that TokenUsage.provider uses, so a peer can tell before sending whether a handle is one it can use.
     * @param string|null $mimeType What the file is, when the producer knows. Optional, because the provider that holds the bytes knows.
     */
    public function __construct(
        public private(set) string $value,
        public private(set) ?string $provider = null,
        public private(set) ?string $mimeType = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'value', 'provider', 'mimeType'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'file', Json::pointer($path, 'type'));

        return new self(
            value: Json::string(Json::required($properties, 'value', $path), Json::pointer($path, 'value')),
            provider: \array_key_exists('provider', $properties) && Json::present($properties['provider'], $context, Json::pointer($path, 'provider')) ? Json::string($properties['provider'], Json::pointer($path, 'provider')) : null,
            mimeType: \array_key_exists('mimeType', $properties) && Json::present($properties['mimeType'], $context, Json::pointer($path, 'mimeType')) ? Json::string($properties['mimeType'], Json::pointer($path, 'mimeType')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'file',
            'value' => $this->value,
        ];

        if (null !== $this->provider) {
            $data['provider'] = $this->provider;
        }

        if (null !== $this->mimeType) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }
}
