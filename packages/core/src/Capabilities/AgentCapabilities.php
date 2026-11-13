<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A typed, categorized snapshot of an agent's current capabilities. All fields are optional
 * — agents only declare what they support. An omitted field means the capability is not
 * declared (unknown), not that it is unsupported. The custom field is an escape hatch for
 * integration-specific capabilities that do not fit into the standard categories.
 */
final class AgentCapabilities implements Model
{
    /**
     * @param IdentityCapabilities|null $identity agent identity and metadata
     * @param TransportCapabilities|null $transport Supported transport mechanisms (SSE, WebSocket, binary, etc.).
     * @param ToolsCapabilities|null $tools tools the agent provides and tool calling configuration
     * @param OutputCapabilities|null $output output format support (structured output, MIME types)
     * @param StateCapabilities|null $state state and memory management (snapshots, deltas, persistence)
     * @param MultiAgentCapabilities|null $multiAgent multi-agent coordination (delegation, handoffs, subagents)
     * @param ReasoningCapabilities|null $reasoning reasoning and thinking support (chain-of-thought, encrypted thinking)
     * @param MultimodalCapabilities|null $multimodal multimodal input/output support (images, audio, video, files)
     * @param ExecutionCapabilities|null $execution execution control and limits (code execution, timeouts, iteration caps)
     * @param HumanInTheLoopCapabilities|null $humanInTheLoop human-in-the-loop support (approvals, interventions, feedback)
     * @param array<array-key, mixed>|null $custom Integration-specific capabilities not covered by the standard categories. Open by key: any JSON value is allowed under a key, because the categories above cannot anticipate what an integration declares.
     */
    public function __construct(
        #[Assert\Valid]
        public private(set) ?IdentityCapabilities $identity = null,
        #[Assert\Valid]
        public private(set) ?TransportCapabilities $transport = null,
        #[Assert\Valid]
        public private(set) ?ToolsCapabilities $tools = null,
        #[Assert\Valid]
        public private(set) ?OutputCapabilities $output = null,
        #[Assert\Valid]
        public private(set) ?StateCapabilities $state = null,
        #[Assert\Valid]
        public private(set) ?MultiAgentCapabilities $multiAgent = null,
        #[Assert\Valid]
        public private(set) ?ReasoningCapabilities $reasoning = null,
        #[Assert\Valid]
        public private(set) ?MultimodalCapabilities $multimodal = null,
        #[Assert\Valid]
        public private(set) ?ExecutionCapabilities $execution = null,
        #[Assert\Valid]
        public private(set) ?HumanInTheLoopCapabilities $humanInTheLoop = null,
        public private(set) ?array $custom = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['identity', 'transport', 'tools', 'output', 'state', 'multiAgent', 'reasoning', 'multimodal', 'execution', 'humanInTheLoop', 'custom'], $context, $path);

        return new self(
            identity: \array_key_exists('identity', $properties) && Json::present($properties['identity'], $context, Json::pointer($path, 'identity')) ? IdentityCapabilities::fromArray(Json::object($properties['identity'], Json::pointer($path, 'identity')), $context, Json::pointer($path, 'identity')) : null,
            transport: \array_key_exists('transport', $properties) && Json::present($properties['transport'], $context, Json::pointer($path, 'transport')) ? TransportCapabilities::fromArray(Json::object($properties['transport'], Json::pointer($path, 'transport')), $context, Json::pointer($path, 'transport')) : null,
            tools: \array_key_exists('tools', $properties) && Json::present($properties['tools'], $context, Json::pointer($path, 'tools')) ? ToolsCapabilities::fromArray(Json::object($properties['tools'], Json::pointer($path, 'tools')), $context, Json::pointer($path, 'tools')) : null,
            output: \array_key_exists('output', $properties) && Json::present($properties['output'], $context, Json::pointer($path, 'output')) ? OutputCapabilities::fromArray(Json::object($properties['output'], Json::pointer($path, 'output')), $context, Json::pointer($path, 'output')) : null,
            state: \array_key_exists('state', $properties) && Json::present($properties['state'], $context, Json::pointer($path, 'state')) ? StateCapabilities::fromArray(Json::object($properties['state'], Json::pointer($path, 'state')), $context, Json::pointer($path, 'state')) : null,
            multiAgent: \array_key_exists('multiAgent', $properties) && Json::present($properties['multiAgent'], $context, Json::pointer($path, 'multiAgent')) ? MultiAgentCapabilities::fromArray(Json::object($properties['multiAgent'], Json::pointer($path, 'multiAgent')), $context, Json::pointer($path, 'multiAgent')) : null,
            reasoning: \array_key_exists('reasoning', $properties) && Json::present($properties['reasoning'], $context, Json::pointer($path, 'reasoning')) ? ReasoningCapabilities::fromArray(Json::object($properties['reasoning'], Json::pointer($path, 'reasoning')), $context, Json::pointer($path, 'reasoning')) : null,
            multimodal: \array_key_exists('multimodal', $properties) && Json::present($properties['multimodal'], $context, Json::pointer($path, 'multimodal')) ? MultimodalCapabilities::fromArray(Json::object($properties['multimodal'], Json::pointer($path, 'multimodal')), $context, Json::pointer($path, 'multimodal')) : null,
            execution: \array_key_exists('execution', $properties) && Json::present($properties['execution'], $context, Json::pointer($path, 'execution')) ? ExecutionCapabilities::fromArray(Json::object($properties['execution'], Json::pointer($path, 'execution')), $context, Json::pointer($path, 'execution')) : null,
            humanInTheLoop: \array_key_exists('humanInTheLoop', $properties) && Json::present($properties['humanInTheLoop'], $context, Json::pointer($path, 'humanInTheLoop')) ? HumanInTheLoopCapabilities::fromArray(Json::object($properties['humanInTheLoop'], Json::pointer($path, 'humanInTheLoop')), $context, Json::pointer($path, 'humanInTheLoop')) : null,
            custom: \array_key_exists('custom', $properties) && Json::present($properties['custom'], $context, Json::pointer($path, 'custom')) ? Json::openObject($properties['custom'], Json::pointer($path, 'custom')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->identity) {
            $data['identity'] = Json::encodeObject($this->identity->toArray());
        }

        if (null !== $this->transport) {
            $data['transport'] = Json::encodeObject($this->transport->toArray());
        }

        if (null !== $this->tools) {
            $data['tools'] = Json::encodeObject($this->tools->toArray());
        }

        if (null !== $this->output) {
            $data['output'] = Json::encodeObject($this->output->toArray());
        }

        if (null !== $this->state) {
            $data['state'] = Json::encodeObject($this->state->toArray());
        }

        if (null !== $this->multiAgent) {
            $data['multiAgent'] = Json::encodeObject($this->multiAgent->toArray());
        }

        if (null !== $this->reasoning) {
            $data['reasoning'] = Json::encodeObject($this->reasoning->toArray());
        }

        if (null !== $this->multimodal) {
            $data['multimodal'] = Json::encodeObject($this->multimodal->toArray());
        }

        if (null !== $this->execution) {
            $data['execution'] = Json::encodeObject($this->execution->toArray());
        }

        if (null !== $this->humanInTheLoop) {
            $data['humanInTheLoop'] = Json::encodeObject($this->humanInTheLoop->toArray());
        }

        if (null !== $this->custom) {
            $data['custom'] = Json::encodeObject($this->custom);
        }

        return $data;
    }
}
