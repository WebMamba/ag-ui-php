<?php

declare(strict_types=1);

namespace AgUi\Serializer;

use AgUi\Exception\InvalidShapeException;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Plugs the AG-UI model into Symfony Serializer: any protocol type, or any union of
 * them (Event, Message…), can be (de)serialized with the Serializer's usual API.
 *
 * Decoding is lenient about unknown properties, like the Serializer itself, unless the
 * context sets AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES to false.
 */
final class ModelNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|\ArrayObject
    {
        \assert($data instanceof Model);

        $normalized = $data->toArray();

        // {} rather than [] for an empty object, as everywhere else in the model.
        return [] === $normalized ? new \ArrayObject() : $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Model;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Model
    {
        $decodeContext = new DecodeContext(strict: false === ($context[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] ?? true));

        try {
            if (interface_exists($type)) {
                $decode = [$type.'s', 'fromArray'];
                $model = \is_callable($decode) ? $decode($data, $decodeContext) : null;

                return $model instanceof Model ? $model : throw new \LogicException(\sprintf('"%ss" does not decode a %s.', $type, $type));
            }

            if (!is_a($type, Model::class, true)) {
                throw new \LogicException(\sprintf('"%s" is not an AG-UI model.', $type));
            }

            return $type::fromArray(Json::object($data, ''), $decodeContext);
        } catch (InvalidShapeException $e) {
            throw NotNormalizableValueException::createForUnexpectedDataType($e->getMessage(), $data, [$type], self::propertyPath($e->instanceLocation), true, $e->getCode(), $e);
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!is_a($type, Model::class, true)) {
            return false;
        }

        // A union is decoded by its dispatcher: Event by Events, Message by Messages…
        return !interface_exists($type) || class_exists($type.'s');
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true, '*' => false];
    }

    /** "/messages/0/content" → "messages[0].content", the Serializer's own notation. */
    private static function propertyPath(string $pointer): ?string
    {
        if ('' === $pointer) {
            return null;
        }

        $path = '';
        foreach (explode('/', substr($pointer, 1)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
            $path .= ctype_digit($segment) ? "[{$segment}]" : ('' === $path ? $segment : ".{$segment}");
        }

        return $path;
    }
}
