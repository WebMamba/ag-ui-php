<?php

declare(strict_types=1);

namespace AgUi\Bundle\DependencyInjection\Compiler;

use AgUi\SymfonyAi\Context\AgentContext;
use AgUi\SymfonyAi\Context\ContextEntry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Gives AgUi the agent context: the methods marked #[AsAgentContext], each for one
 * agent or for every agent. AgUi tells each agent it runs its own.
 */
final class AgentContextPass implements CompilerPassInterface
{
    public const string TAG = 'ag_ui.agent_context';

    public function process(ContainerBuilder $container): void
    {
        /** @var list<Definition> $entries */
        $entries = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                if (!\is_array($tag)) {
                    continue;
                }
                $description = $tag['description'] ?? null;
                $method = $tag['method'] ?? null;
                if (!\is_string($description) || !\is_string($method)) {
                    throw new InvalidArgumentException(\sprintf('The "%s" tag of service "%s" needs a "description" and a "method".', self::TAG, $id));
                }

                self::assertReadable($container, $id, $method, $description);

                $read = (new Definition(\Closure::class))
                    ->setFactory([\Closure::class, 'fromCallable'])
                    ->setArguments([[new Reference($id), $method]]);

                $agent = $tag['agent'] ?? null;
                $entries[] = new Definition(ContextEntry::class, [$description, $read, \is_string($agent) ? self::agentName($agent) : null]);
            }
        }

        if (!$container->hasDefinition('ag_ui')) {
            return;
        }

        $container->getDefinition('ag_ui')->setArgument('$context', new Definition(AgentContext::class, [
            $entries,
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]));
    }

    /** An agent named as it is under "ai.agent", or by its service id. */
    private static function agentName(string $agent): string
    {
        return str_starts_with($agent, 'ai.agent.') ? substr($agent, \strlen('ai.agent.')) : $agent;
    }

    /** A mistake here would only show at the first run: catch it while compiling. */
    private static function assertReadable(ContainerBuilder $container, string $id, string $method, string $description): void
    {
        $class = $container->getReflectionClass($container->findDefinition($id)->getClass(), false);
        if (null === $class) {
            return;
        }

        if (!$class->hasMethod($method) || !$class->getMethod($method)->isPublic()) {
            throw new InvalidArgumentException(\sprintf('The agent context "%s" reads %s::%s(), which is not a public method.', $description, $class->getName(), $method));
        }

        $type = $class->getMethod($method)->getReturnType();
        if (!$type instanceof \ReflectionNamedType || 'string' !== $type->getName()) {
            throw new InvalidArgumentException(\sprintf('The agent context "%s" reads %s::%s(), which must return string or ?string.', $description, $class->getName(), $method));
        }
    }
}
