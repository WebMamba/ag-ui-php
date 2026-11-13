<?php

declare(strict_types=1);

namespace AgUi\Bundle\Component;

use AgUi\SymfonyAi\Activity\Activity;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

/**
 * Renders a Twig component as an activity: what agent components return, and what a
 * tool uses to give an interrupt its UI —.
 *
 *     return Interrupt::confirmation('Reserve?')->withActivity($this->activities->create('MooringReservation', $props));
 */
final readonly class ComponentActivityFactory
{
    public const string ACTIVITY_TYPE = 'twig_component';

    public function __construct(
        private ComponentRendererInterface $renderer,
    ) {
    }

    /** @param array<string, mixed> $props */
    public function create(string $component, array $props = [], ?string $forModel = null): Activity
    {
        return new Activity(
            $forModel ?? \sprintf('Displayed the %s component to the user.', $component),
            self::ACTIVITY_TYPE,
            ['component' => $component, 'props' => $props, 'html' => $this->renderer->createAndRender($component, $props)],
        );
    }
}
