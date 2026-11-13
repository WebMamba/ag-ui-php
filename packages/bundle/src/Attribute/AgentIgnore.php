<?php

declare(strict_types=1);

namespace AgUi\Bundle\Attribute;

/**
 * Keeps a public property of an agent component out of its tool: the model never fills
 * it, and the component falls back to its default.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class AgentIgnore
{
}
