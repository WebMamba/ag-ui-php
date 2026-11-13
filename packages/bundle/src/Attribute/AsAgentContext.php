<?php

declare(strict_types=1);

namespace AgUi\Bundle\Attribute;

/**
 * States a fact to the agent: the method's return value is read at the start of every
 * run, and again after each tool — the model is told when it changed.
 *
 *     #[AsAgentContext('Interface theme')]
 *     final class ThemeState
 *     {
 *         public function __invoke(): ?string { ... }   // null leaves the fact out
 *     }
 *
 * On a class, it reads $method; on a method, that method.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class AsAgentContext
{
    /**
     * @param string $description what the value is, as the model reads it
     * @param string|null $agent the Symfony AI agent told about it, by name or service id; every agent by default
     * @param string $method the method returning the value, when the attribute is on the class
     */
    public function __construct(
        public string $description,
        public ?string $agent = null,
        public string $method = '__invoke',
    ) {
    }
}
