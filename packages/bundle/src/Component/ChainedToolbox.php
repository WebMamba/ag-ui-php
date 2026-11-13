<?php

declare(strict_types=1);

namespace AgUi\Bundle\Component;

use AgUi\SymfonyAi\Run\RunScope;
use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Agent\Toolbox\ToolResult;
use Symfony\AI\Platform\Result\ToolCall;

/**
 * An agent's own toolbox, plus the agent components while AgUi runs that agent: they
 * render to an AG-UI client, so no other call is offered them.
 *
 * @internal
 */
final readonly class ChainedToolbox implements ToolboxInterface
{
    public function __construct(
        private ToolboxInterface $agentToolbox,
        private AgentComponentToolbox $components,
        private RunScope $scope,
        private string $agent,
    ) {
    }

    public function getTools(): array
    {
        if (!$this->scope->isOpenFor($this->agent)) {
            return $this->agentToolbox->getTools();
        }

        return [...$this->agentToolbox->getTools(), ...$this->components->getTools()];
    }

    public function execute(ToolCall $toolCall): ToolResult
    {
        return $this->scope->isOpenFor($this->agent) && $this->components->has($toolCall->getName())
            ? $this->components->execute($toolCall)
            : $this->agentToolbox->execute($toolCall);
    }
}
