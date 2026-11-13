<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Basic metadata about the agent. Useful for discovery UIs, agent marketplaces, and debugging.
 * Set these when you want clients to display agent information or when multiple agents are
 * available and users need to pick one.
 */
final class IdentityCapabilities implements Model
{
    /**
     * @param string|null $name human-readable name shown in UIs and agent selectors
     * @param string|null $type The framework or platform powering this agent (e.g., "langgraph", "mastra", "crewai").
     * @param string|null $description what this agent does — helps users and routing logic decide when to use it
     * @param string|null $version Semantic version of the agent (e.g., "1.2.0"). Useful for compatibility checks.
     * @param string|null $provider organization or team that maintains this agent
     * @param string|null $documentationUrl URL to the agent's documentation or homepage
     * @param array<array-key, mixed>|null $metadata arbitrary key-value pairs for integration-specific identity info
     */
    public function __construct(
        public private(set) ?string $name = null,
        public private(set) ?string $type = null,
        public private(set) ?string $description = null,
        public private(set) ?string $version = null,
        public private(set) ?string $provider = null,
        public private(set) ?string $documentationUrl = null,
        public private(set) ?array $metadata = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['name', 'type', 'description', 'version', 'provider', 'documentationUrl', 'metadata'], $context, $path);

        return new self(
            name: \array_key_exists('name', $properties) && Json::present($properties['name'], $context, Json::pointer($path, 'name')) ? Json::string($properties['name'], Json::pointer($path, 'name')) : null,
            type: \array_key_exists('type', $properties) && Json::present($properties['type'], $context, Json::pointer($path, 'type')) ? Json::string($properties['type'], Json::pointer($path, 'type')) : null,
            description: \array_key_exists('description', $properties) && Json::present($properties['description'], $context, Json::pointer($path, 'description')) ? Json::string($properties['description'], Json::pointer($path, 'description')) : null,
            version: \array_key_exists('version', $properties) && Json::present($properties['version'], $context, Json::pointer($path, 'version')) ? Json::string($properties['version'], Json::pointer($path, 'version')) : null,
            provider: \array_key_exists('provider', $properties) && Json::present($properties['provider'], $context, Json::pointer($path, 'provider')) ? Json::string($properties['provider'], Json::pointer($path, 'provider')) : null,
            documentationUrl: \array_key_exists('documentationUrl', $properties) && Json::present($properties['documentationUrl'], $context, Json::pointer($path, 'documentationUrl')) ? Json::string($properties['documentationUrl'], Json::pointer($path, 'documentationUrl')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->name) {
            $data['name'] = $this->name;
        }

        if (null !== $this->type) {
            $data['type'] = $this->type;
        }

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if (null !== $this->version) {
            $data['version'] = $this->version;
        }

        if (null !== $this->provider) {
            $data['provider'] = $this->provider;
        }

        if (null !== $this->documentationUrl) {
            $data['documentationUrl'] = $this->documentationUrl;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        return $data;
    }
}
