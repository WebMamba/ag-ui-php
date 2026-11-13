<?php

declare(strict_types=1);

namespace AgUi\Bundle\DependencyInjection\Compiler;

use AgUi\Bundle\Attribute\AgentIgnore;
use Symfony\AI\Platform\Contract\JsonSchema\Factory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Turns every #[AsAgentComponent] Twig component into a tool definition: its public
 * properties, minus the #[AgentIgnore] ones, are the parameters. A component the model
 * could not render fails the build, rather than give the model a broken tool.
 */
final class AgentComponentsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ag_ui.agent_components')) {
            return;
        }

        /** @var array<class-string, string> $names */
        $names = $container->hasDefinition('ux.twig_component.component_factory')
            ? $container->findDefinition('ux.twig_component.component_factory')->getArgument(5)
            : [];

        $factory = new Factory();
        $tools = [];

        foreach ($container->findTaggedServiceIds('ag_ui.agent_component') as $id => $tags) {
            /** @var class-string $class */
            $class = $container->findDefinition($id)->getClass() ?? $id;
            $component = $names[$class] ?? throw new LogicException(\sprintf('"%s" has #[AsAgentComponent] but is not a Twig component: add #[AsTwigComponent].', $class));
            $tag = \is_array($tags[0] ?? null) ? $tags[0] : [];
            $description = \is_string($tag['description'] ?? null) ? $tag['description'] : throw new LogicException(\sprintf('The agent component "%s" needs a description.', $class));

            self::assertMountable($class);

            $tool = \is_string($tag['tool'] ?? null)
                ? $tag['tool']
                : str_replace('__', '_', 'render_'.strtolower((string) preg_replace(['/:/', '/(?<!^)[A-Z]/'], ['_', '_$0'], $component)));

            $tools[$tool] = [
                'component' => $component,
                'class' => $class,
                'description' => $description,
                'forModel' => \is_string($tag['for_model'] ?? null) ? $tag['for_model'] : null,
                'parameters' => self::parameters($factory, $class),
            ];
        }

        $container->getDefinition('ag_ui.agent_components')->setArgument(1, $tools);
    }

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>|null
     */
    private static function parameters(Factory $factory, string $class): ?array
    {
        $ignored = [];
        $optional = [];
        foreach ((new \ReflectionClass($class))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ([] !== $property->getAttributes(AgentIgnore::class)) {
                $ignored[] = $property->getName();
            } elseif ($property->hasDefaultValue()) {
                // The component falls back to its default: the model may leave it out.
                $optional[] = $property->getName();
            }
        }

        try {
            $schema = $factory->buildProperties($class);
        } catch (\Throwable $e) {
            throw new LogicException(\sprintf('The agent component "%s" cannot be described to the model: %s Mark the property the model must not fill with #[AgentIgnore].', $class, $e->getMessage()), previous: $e);
        }

        if (null === $schema) {
            return null;
        }

        foreach ($ignored as $name) {
            unset($schema['properties'][$name]);
        }
        $schema['required'] = array_values(array_diff($schema['required'], $ignored, $optional));

        return [] === $schema['properties'] ? null : $schema;
    }

    /** @param class-string $class */
    private static function assertMountable(string $class): void
    {
        if (!method_exists($class, 'mount')) {
            return;
        }

        foreach ((new \ReflectionMethod($class, 'mount'))->getParameters() as $parameter) {
            if (!$parameter->isOptional()) {
                throw new LogicException(\sprintf('The agent component "%s" needs "$%s" to mount, which its public properties cannot give it: make the argument optional, or expose a public property instead.', $class, $parameter->getName()));
            }
        }
    }
}
