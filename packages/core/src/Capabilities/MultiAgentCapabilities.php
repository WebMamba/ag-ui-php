<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Multi-agent coordination capabilities. Enable these when your agent can orchestrate or hand
 * off work to other agents.
 */
final class MultiAgentCapabilities implements Model
{
    /**
     * @param bool|null $supported set true if the agent participates in any form of multi-agent coordination
     * @param bool|null $delegation set true if the agent can delegate subtasks to other agents while retaining control
     * @param bool|null $handoffs set true if the agent can transfer the conversation entirely to another agent
     * @param list<SubagentInfo>|null $subagents List of subagents this agent can invoke. Helps clients build agent selection UIs.
     */
    public function __construct(
        public private(set) ?bool $supported = null,
        public private(set) ?bool $delegation = null,
        public private(set) ?bool $handoffs = null,
        #[Assert\Valid]
        public private(set) ?array $subagents = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['supported', 'delegation', 'handoffs', 'subagents'], $context, $path);

        return new self(
            supported: \array_key_exists('supported', $properties) && Json::present($properties['supported'], $context, Json::pointer($path, 'supported')) ? Json::bool($properties['supported'], Json::pointer($path, 'supported')) : null,
            delegation: \array_key_exists('delegation', $properties) && Json::present($properties['delegation'], $context, Json::pointer($path, 'delegation')) ? Json::bool($properties['delegation'], Json::pointer($path, 'delegation')) : null,
            handoffs: \array_key_exists('handoffs', $properties) && Json::present($properties['handoffs'], $context, Json::pointer($path, 'handoffs')) ? Json::bool($properties['handoffs'], Json::pointer($path, 'handoffs')) : null,
            subagents: \array_key_exists('subagents', $properties) && Json::present($properties['subagents'], $context, Json::pointer($path, 'subagents')) ? Json::list($properties['subagents'], Json::pointer($path, 'subagents'), static fn (mixed $item, string $at) => SubagentInfo::fromArray(Json::object($item, $at), $context, $at)) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->supported) {
            $data['supported'] = $this->supported;
        }

        if (null !== $this->delegation) {
            $data['delegation'] = $this->delegation;
        }

        if (null !== $this->handoffs) {
            $data['handoffs'] = $this->handoffs;
        }

        if (null !== $this->subagents) {
            $data['subagents'] = array_map(static fn (SubagentInfo $item) => Json::encodeObject($item->toArray()), $this->subagents);
        }

        return $data;
    }
}
