<?php

declare(strict_types=1);

namespace AgUi\Bundle\Attribute;

/**
 * Exposes a Twig component to the model as a tool: its public properties are the tool's
 * parameters, and calling it renders the component as an activity the client shows.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsAgentComponent
{
    /**
     * @param string $description when the model should show this component
     * @param string|null $tool the tool's name; "render_" and the component's name by default
     * @param string|null $forModel what the model reads once it is shown; a sentence naming it by default
     */
    public function __construct(
        public string $description,
        public ?string $tool = null,
        public ?string $forModel = null,
    ) {
    }
}
