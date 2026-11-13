<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App\Controller;

use AgUi\Bundle\Http\AgUiResponse;
use AgUi\Bundle\Tests\App\Cart;
use AgUi\Run\RunAgentInput;
use AgUi\SymfonyAi\AgUi;
use AgUi\SymfonyAi\Exception\InvalidStateException;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** The application's own routes: each one picks the agent it runs, by name. */
#[AsController]
final readonly class ShopController
{
    public function __construct(
        private AgUi $agUi,
    ) {
    }

    #[Route('/agent/shop', methods: ['POST'])]
    public function shop(RunAgentInput $input, AgentInterface $shop, Cart $cart): AgUiResponse
    {
        try {
            return new AgUiResponse($this->agUi->run($input, $shop, state: $cart));
        } catch (InvalidStateException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }
    }

    #[Route('/agent/support', methods: ['POST'])]
    public function support(RunAgentInput $input, AgentInterface $support): AgUiResponse
    {
        return new AgUiResponse($this->agUi->run($input, $support));
    }
}
