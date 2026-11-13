<?php

declare(strict_types=1);

namespace AgUi\Bundle\Http;

use AgUi\Exception\InvalidShapeException;
use AgUi\Run\RunAgentInput;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Gives a controller argument typed RunAgentInput the request's body, decoded and
 * checked against the protocol's schema; a body that is not one is a 422.
 */
final readonly class RunAgentInputValueResolver implements ValueResolverInterface
{
    /** @return iterable<RunAgentInput> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (RunAgentInput::class !== $argument->getType()) {
            return [];
        }

        return [self::decode($request)];
    }

    public static function decode(Request $request): RunAgentInput
    {
        try {
            return RunAgentInput::fromArray($request->toArray());
        } catch (JsonException|InvalidShapeException $e) {
            throw new UnprocessableEntityHttpException(\sprintf('Not an AG-UI RunAgentInput: %s', $e->getMessage()), $e);
        }
    }
}
