<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Tool;

use AgUi\Bundle\Tests\App\Context\ShopHours;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('close_shop', 'Closes the shop.')]
final class CloseShop
{
    public function __invoke(): string
    {
        ShopHours::$status = 'closed';

        return 'Closed.';
    }
}
