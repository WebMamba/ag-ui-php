<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Context;

use AgUi\Bundle\Attribute\AsAgentContext;

/** Facts on methods: one for this agent, one left out, one for another agent. */
final class Customer
{
    #[AsAgentContext('Customer', agent: 'shop')]
    public function name(): ?string
    {
        return 'Moana';
    }

    #[AsAgentContext('Coupon')]
    public function coupon(): ?string
    {
        return null;
    }

    #[AsAgentContext('Warehouse', agent: 'support')]
    public function warehouse(): string
    {
        return 'Papeete';
    }
}
