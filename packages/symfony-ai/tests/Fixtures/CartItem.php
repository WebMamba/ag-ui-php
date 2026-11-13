<?php

declare(strict_types=1);

namespace AgUi\SymfonyAi\Tests\Fixtures;

final class CartItem
{
    public function __construct(
        public string $sku,
        public int $qty,
    ) {
    }
}
