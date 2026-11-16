<?php

declare(strict_types=1);

namespace AgUi\Bundle;

use AgUi\Bridge\SymfonyAi\AgUi;
use AgUi\Bridge\SymfonyAi\Interrupt\ResumableTool;
use AgUi\Bridge\SymfonyAi\Interrupt\ResumableTools;
use AgUi\Bridge\SymfonyAi\Mapping\ToolResultMapping;
use AgUi\Bridge\SymfonyAi\Run\RunContext;
use AgUi\Bridge\SymfonyAi\Run\RunScope;
use AgUi\Bundle\Attribute\AsAgentComponent;
use AgUi\Bundle\Attribute\AsAgentContext;
use AgUi\Bundle\Component\AgentComponentToolbox;
use AgUi\Bundle\Component\ComponentActivityFactory;
use AgUi\Bundle\DependencyInjection\Compiler\AgentComponentsPass;
use AgUi\Bundle\DependencyInjection\Compiler\AgentContextPass;
use AgUi\Bundle\DependencyInjection\Compiler\WireAgentsPass;
use AgUi\Bundle\Http\RunAgentInputValueResolver;
use AgUi\Stream\RunStream;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

/**
 * Every Symfony AI agent declared under "ai.agent" speaks AG-UI: no configuration.
 * The route is yours: inject the agent by its name, and run it with AgUi.
 *
 *     #[Route('/agent/shop', methods: ['POST'])]
 *     public function __invoke(RunAgentInput $input, AgentInterface $shop, AgUi $agUi, Cart $cart): AgUiResponse
 *     {
 *         return new AgUiResponse($agUi->run($input, $shop, state: $cart));
 *     }
 *
 * Each agent gets the protocol's tools — the client's frontend tools, the agent
 * components — only while AgUi runs it: called anywhere else, it is unchanged.
 *
 * What the server knows and the agent should too is stated with #[AsAgentContext].
 */
final class AgUiBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ag_ui';

    /** What may require an optional package for the bundle: the bundle itself, or the monorepo it is developed in. */
    private const array PACKAGES = ['webmamba/ag-ui-bundle', 'webmamba/ag-ui-php'];

    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set('ag_ui.run_scope', RunScope::class)->tag('kernel.reset', ['method' => 'reset']);
        $services->set('ag_ui.run_context', RunContext::class)->args([new Reference('ag_ui.run_scope')]);
        $services->alias(RunContext::class, 'ag_ui.run_context');

        $services->set('ag_ui.run_stream', RunStream::class)->args([
            new Reference('validator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('clock', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%kernel.debug%',
        ])->tag('monolog.logger', ['channel' => 'ag_ui']);
        $services->alias(RunStream::class, 'ag_ui.run_stream');

        $services->set('ag_ui.resumable_tools', ResumableTools::class)->args([new TaggedIteratorArgument('ag_ui.resumable_tool')]);

        $builder->registerForAutoconfiguration(ResumableTool::class)->addTag('ag_ui.resumable_tool');
        $builder->registerForAutoconfiguration(ToolResultMapping::class)->addTag('ag_ui.tool_result_mapping');
        $builder->registerAttributeForAutoconfiguration(AsAgentContext::class, static function (ChildDefinition $definition, AsAgentContext $attribute, \Reflector $reflector): void {
            $definition->addTag(AgentContextPass::TAG, [
                'description' => $attribute->description,
                'agent' => $attribute->agent,
                'method' => $reflector instanceof \ReflectionMethod ? $reflector->getName() : $attribute->method,
            ]);
        });

        if (ContainerBuilder::willBeAvailable('symfony/ux-twig-component', ComponentRendererInterface::class, self::PACKAGES)) {
            $services->set('ag_ui.component_activity_factory', ComponentActivityFactory::class)->args([new Reference(ComponentRendererInterface::class)]);
            $services->alias(ComponentActivityFactory::class, 'ag_ui.component_activity_factory');
            $services->set('ag_ui.agent_components', AgentComponentToolbox::class)->args([
                new Reference('ag_ui.component_activity_factory'),
                [],
                new Reference('serializer', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('property_info', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ]);

            $builder->registerAttributeForAutoconfiguration(AsAgentComponent::class, static function (ChildDefinition $definition, AsAgentComponent $attribute): void {
                $definition->addTag('ag_ui.agent_component', array_filter(['description' => $attribute->description, 'tool' => $attribute->tool, 'for_model' => $attribute->forModel], static fn (?string $v): bool => null !== $v));
            });
        }

        $services->set('ag_ui', AgUi::class)->args([
            '$secret' => '%kernel.secret%',
            '$scope' => new Reference('ag_ui.run_scope'),
            '$resumableTools' => new Reference('ag_ui.resumable_tools'),
            '$mappings' => new TaggedIteratorArgument('ag_ui.tool_result_mapping'),
            '$stream' => new Reference('ag_ui.run_stream'),
            '$serializer' => new Reference('serializer', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '$validator' => new Reference('validator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);
        $services->alias(AgUi::class, 'ag_ui');

        $services->set('ag_ui.run_agent_input_resolver', RunAgentInputValueResolver::class)->tag('controller.argument_value_resolver', ['priority' => 50]);
    }

    /** The Stimulus controllers in assets/, for AssetMapper to serve (as Symfony UX packages do). */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!ContainerBuilder::willBeAvailable('symfony/asset-mapper', AssetMapperInterface::class, self::PACKAGES)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => ['paths' => [__DIR__.'/../assets/src' => '@webmamba/ag-ui-bundle']],
        ]);
    }

    public function build(ContainerBuilder $container): void
    {
        // Before Symfony AI attaches input processors (priority 0) and before any decoration.
        $container->addCompilerPass(new WireAgentsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 10);
        // After Twig Components names its components (priority 0).
        $container->addCompilerPass(new AgentComponentsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
        // After autoconfiguration (100) has tagged the #[AsAgentContext] services.
        $container->addCompilerPass(new AgentContextPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
    }
}
