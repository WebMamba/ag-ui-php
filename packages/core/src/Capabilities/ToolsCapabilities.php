<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tool calling capabilities. Distinguishes between tools the agent itself provides (listed in
 * items) and tools the client passes at runtime via RunAgentInput.tools. Enable this when your
 * agent can call functions, search the web, execute code, etc.
 */
final class ToolsCapabilities implements Model
{
    /**
     * @param bool|null $supported Set true if the agent can make tool calls at all. Set false to explicitly signal tool calling is disabled even if items are present.
     * @param list<\AgUi\Run\Tool>|null $items The tools this agent provides on its own (full JSON Schema definitions). These are distinct from client-provided tools passed in RunAgentInput.tools.
     * @param bool|null $parallelCalls set true if the agent can invoke multiple tools concurrently within a single step
     * @param bool|null $clientProvided set true if the agent accepts and uses tools provided by the client at runtime
     */
    public function __construct(
        public private(set) ?bool $supported = null,
        #[Assert\Valid]
        public private(set) ?array $items = null,
        public private(set) ?bool $parallelCalls = null,
        public private(set) ?bool $clientProvided = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['supported', 'items', 'parallelCalls', 'clientProvided'], $context, $path);

        return new self(
            supported: \array_key_exists('supported', $properties) && Json::present($properties['supported'], $context, Json::pointer($path, 'supported')) ? Json::bool($properties['supported'], Json::pointer($path, 'supported')) : null,
            items: \array_key_exists('items', $properties) && Json::present($properties['items'], $context, Json::pointer($path, 'items')) ? Json::list($properties['items'], Json::pointer($path, 'items'), static fn (mixed $item, string $at) => \AgUi\Run\Tool::fromArray(Json::object($item, $at), $context, $at)) : null,
            parallelCalls: \array_key_exists('parallelCalls', $properties) && Json::present($properties['parallelCalls'], $context, Json::pointer($path, 'parallelCalls')) ? Json::bool($properties['parallelCalls'], Json::pointer($path, 'parallelCalls')) : null,
            clientProvided: \array_key_exists('clientProvided', $properties) && Json::present($properties['clientProvided'], $context, Json::pointer($path, 'clientProvided')) ? Json::bool($properties['clientProvided'], Json::pointer($path, 'clientProvided')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->supported) {
            $data['supported'] = $this->supported;
        }

        if (null !== $this->items) {
            $data['items'] = array_map(static fn (\AgUi\Run\Tool $item) => Json::encodeObject($item->toArray()), $this->items);
        }

        if (null !== $this->parallelCalls) {
            $data['parallelCalls'] = $this->parallelCalls;
        }

        if (null !== $this->clientProvided) {
            $data['clientProvided'] = $this->clientProvided;
        }

        return $data;
    }
}
