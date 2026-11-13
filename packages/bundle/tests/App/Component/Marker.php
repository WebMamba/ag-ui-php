<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Component;

final readonly class Marker
{
    public function __construct(
        public string $title,
        public float $lat = 0.0,
    ) {
    }
}
