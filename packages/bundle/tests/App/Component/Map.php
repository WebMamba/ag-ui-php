<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Component;

use AgUi\Bundle\Attribute\AgentIgnore;
use AgUi\Bundle\Attribute\AsAgentComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Map', template: 'components/Map.html.twig')]
#[AsAgentComponent('Shows a map of a place to the user.')]
final class Map
{
    /** The place to center the map on. */
    public string $place;

    public int $zoom = 8;

    /** @var list<Marker> Pins to show on the map. */
    public array $markers = [];

    #[AgentIgnore]
    public ?\DateTimeInterface $renderedAt = null;
}
