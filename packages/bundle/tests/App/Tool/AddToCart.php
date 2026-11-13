<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Tool;

use AgUi\Bundle\Tests\App\Cart;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('add_to_cart', 'Adds a product to the cart.')]
final readonly class AddToCart
{
    public function __construct(private Cart $cart)
    {
    }

    public function __invoke(string $sku): string
    {
        $this->cart->skus[] = $sku;

        return 'Added.';
    }
}
