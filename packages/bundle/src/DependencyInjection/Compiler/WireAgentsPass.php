<?php

declare(strict_types=1);

namespace AgUi\Bundle\DependencyInjection\Compiler;

use AgUi\Bridge\SymfonyAi\InputProcessor\RunInstructionsProcessor;
use AgUi\Bridge\SymfonyAi\Toolbox\AgUiToolExecutor;
use AgUi\Bridge\SymfonyAi\Toolbox\FrontendToolbox;
use AgUi\Bundle\Component\ChainedToolbox;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Prepares every agent declared under "ai.agent" for AG-UI, without the application
 * touching its configuration:
 *
 *  - its toolbox is decorated to add the frontend tools and the agent components;
 *  - its tool executor becomes the bridge's, which reports results and suspends runs;
 *  - the run instructions processor runs right after its system prompt one.
 *
 * The three pieces know the agent's name, and stay out of the way until AgUi runs that
 * agent: called anywhere else, the agent works as before.
 */
final class WireAgentsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('ai.agent') as $id => $tags) {
            $definition = $container->getDefinition($id);
            // A multi-agent hands off to agents that are wired themselves.
            if (Agent::class !== $definition->getClass()) {
                continue;
            }

            $name = $definition->getArguments()['$name'] ?? null;
            $this->wire($container, $id, \is_string($name) ? $name : $id);
        }
    }

    private function wire(ContainerBuilder $container, string $agentId, string $name): void
    {
        $agent = $container->getDefinition($agentId);
        $suffix = str_starts_with($agentId, 'ai.agent.') ? substr($agentId, \strlen('ai.agent.')) : $agentId;
        $frontendToolboxId = 'ag_ui.frontend_toolbox.'.$suffix;
        $components = $container->hasDefinition('ag_ui.agent_components');

        $agentToolboxId = 'ai.toolbox.'.$suffix;
        if (!\array_key_exists('$toolbox', $agent->getArguments()) || !$container->has($agentToolboxId)) {
            // An agent without tools still gets frontend tools and agent components.
            $agentToolboxId = 'ag_ui.empty_toolbox.'.$suffix;
            $container->setDefinition($agentToolboxId, new Definition(Toolbox::class, [[]]));
            $agent->setArgument('$toolbox', new Reference($agentToolboxId));
        }

        $inner = new Reference($frontendToolboxId.'.inner');
        if ($components) {
            $inner = new Definition(ChainedToolbox::class, [$inner, new Reference('ag_ui.agent_components'), new Reference('ag_ui.run_scope'), $name]);
        }

        $container->setDefinition($frontendToolboxId, new Definition(FrontendToolbox::class, [$inner, new Reference('ag_ui.run_scope'), $name]))
            // Inside Symfony AI's own decorators (-1024): the fault-tolerant one turns tool
            // failures into results, and the profiler expects the traceable one outermost.
            ->setDecoratedService($agentToolboxId, null, -512);

        // The executor runs tools through the toolbox as the agent sees it, every decorator included.
        $container->setDefinition('ag_ui.tool_executor.'.$suffix, new Definition(AgUiToolExecutor::class, ['$toolbox' => new Reference($agentToolboxId), '$scope' => new Reference('ag_ui.run_scope'), '$agent' => $name]));
        $agent->setArgument('$toolExecutor', new Reference('ag_ui.tool_executor.'.$suffix));

        // After the system prompt processor (-30): it skips its prompt when the bag already has one.
        $container->register('ag_ui.run_instructions_processor.'.$suffix, RunInstructionsProcessor::class)
            ->setArguments([new Reference('ag_ui.run_scope'), $name])
            ->addTag('ai.agent.input_processor', ['agent' => $agentId, 'priority' => -35]);
    }
}
