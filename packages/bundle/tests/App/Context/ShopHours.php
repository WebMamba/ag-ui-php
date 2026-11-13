<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Context;

use AgUi\Bundle\Attribute\AsAgentContext;

/** A fact for every agent, on an invokable class. */
#[AsAgentContext('Shop')]
final class ShopHours
{
    public static string $status = 'open';

    public function __invoke(): string
    {
        return self::$status;
    }
}
