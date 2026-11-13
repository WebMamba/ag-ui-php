<?php

declare(strict_types=1);

namespace AgUi\Model;

use AgUi\Exception\InvalidShapeException;

/**
 * Reads decoded JSON the way the AG-UI JSON Schema describes it. Used by the generated
 * model classes; every failure names the JSON Pointer of the offending value.
 *
 * @internal
 */
final class Json
{
    /** The largest integer a JSON number survives a round trip with. */
    public const int MAX_SAFE_INTEGER = 9007199254740991;

    /**
     * An object's properties, whether it was decoded as an associative array or as a
     * \stdClass. (PHP turns a numeric key such as "0" into an integer.).
     *
     * @return array<array-key, mixed>
     */
    public static function object(mixed $value, string $path): array
    {
        if ($value instanceof \stdClass) {
            return get_object_vars($value);
        }

        // An associative decode turns {} into [] too, so an empty array passes.
        if (\is_array($value) && ([] === $value || !array_is_list($value))) {
            return $value;
        }

        throw new InvalidShapeException($path, 'type', \sprintf('must be an object, %s given.', self::describe($value)));
    }

    /**
     * Rejects the properties a closed object does not declare, or drops them when the
     * context is lenient.
     *
     * @param array<array-key, mixed> $properties
     * @param list<string> $known
     *
     * @return array<array-key, mixed>
     */
    public static function closed(array $properties, array $known, DecodeContext $context, string $path): array
    {
        foreach (array_diff(array_keys($properties), $known) as $unknown) {
            if ($context->strict) {
                // Reported on the object, as JSON Schema does: the property is not wrong, its presence is.
                throw new InvalidShapeException($path, 'unevaluatedProperties', \sprintf('has no "%s" property.', $unknown));
            }

            $context->drop(self::pointer($path, (string) $unknown));
            unset($properties[$unknown]);
        }

        return $properties;
    }

    /**
     * @param array<array-key, mixed> $properties
     */
    public static function required(array $properties, string $name, string $path): mixed
    {
        if (!\array_key_exists($name, $properties)) {
            throw new InvalidShapeException($path, 'required', \sprintf('the "%s" property is required.', $name));
        }

        return $properties[$name];
    }

    public static function string(mixed $value, string $path): string
    {
        if (!\is_string($value)) {
            throw new InvalidShapeException($path, 'type', \sprintf('must be a string, %s given.', self::describe($value)));
        }

        return $value;
    }

    public static function int(mixed $value, string $path): int
    {
        // JSON Schema's integer is a mathematical integer: 3.0 is one, 3.5 is not.
        if (\is_float($value) && floor($value) === $value && abs($value) <= self::MAX_SAFE_INTEGER) {
            return (int) $value;
        }

        if (!\is_int($value)) {
            throw new InvalidShapeException($path, 'type', \sprintf('must be an integer, %s given.', self::describe($value)));
        }

        return $value;
    }

    public static function bool(mixed $value, string $path): bool
    {
        if (!\is_bool($value)) {
            throw new InvalidShapeException($path, 'type', \sprintf('must be a boolean, %s given.', self::describe($value)));
        }

        return $value;
    }

    /**
     * @template T of string
     *
     * @param T $expected
     *
     * @return T
     */
    public static function const(mixed $value, string $expected, string $path): string
    {
        if ($value !== $expected) {
            throw new InvalidShapeException($path, 'const', \sprintf('must be "%s", %s given.', $expected, self::describe($value)));
        }

        return $expected;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     *
     * @return T
     */
    public static function enum(mixed $value, string $enum, string $path): \BackedEnum
    {
        if (!\is_string($value) || null === $case = $enum::tryFrom($value)) {
            $allowed = implode('", "', array_map(static fn (\BackedEnum $c) => $c->value, $enum::cases()));

            throw new InvalidShapeException($path, 'enum', \sprintf('must be one of "%s", %s given.', $allowed, self::describe($value)));
        }

        return $case;
    }

    /**
     * @template T
     *
     * @param \Closure(mixed, string): T $item
     *
     * @return list<T>
     */
    public static function list(mixed $value, string $path, \Closure $item): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            throw new InvalidShapeException($path, 'type', \sprintf('must be an array, %s given.', self::describe($value)));
        }

        $items = [];
        foreach ($value as $index => $element) {
            $items[] = $item($element, self::pointer($path, (string) $index));
        }

        return $items;
    }

    /**
     * Content that is either plain text or a list of parts; a part the reader does not
     * know (the closure returns null) is left out.
     *
     * @template T of object
     *
     * @param \Closure(mixed, string): (T|null) $item
     *
     * @return string|list<T>
     */
    public static function stringOrList(mixed $value, string $path, \Closure $item): string|array
    {
        if (\is_string($value)) {
            return $value;
        }

        if (!\is_array($value) || !array_is_list($value)) {
            throw new InvalidShapeException($path, 'oneOf', \sprintf('must be a string or an array, %s given.', self::describe($value)));
        }

        return array_values(array_filter(self::list($value, $path, $item)));
    }

    /**
     * The discriminator of a oneOf: the value of the property that tells its members apart.
     *
     * @return array{array<array-key, mixed>, string}
     */
    public static function discriminate(mixed $value, string $property, string $path): array
    {
        if (!$value instanceof \stdClass && !(\is_array($value) && ([] === $value || !array_is_list($value)))) {
            throw new InvalidShapeException($path, 'oneOf', \sprintf('must be an object, %s given.', self::describe($value)));
        }

        $properties = self::object($value, $path);
        $discriminator = $properties[$property] ?? null;

        if (!\is_string($discriminator)) {
            throw new InvalidShapeException($path, 'oneOf', \sprintf('has no "%s" to tell which kind it is.', $property));
        }

        return [$properties, $discriminator];
    }

    /**
     * A value the protocol carries without looking inside: state, a custom event's value,
     * a run's result. Objects become associative arrays, except an empty object, which
     * stays a \stdClass so it is written back as {} rather than [].
     */
    public static function value(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $properties = get_object_vars($value);

            return [] === $properties ? new \stdClass() : array_map(self::value(...), $properties);
        }

        if (\is_array($value)) {
            return array_map(self::value(...), $value);
        }

        return $value;
    }

    /**
     * An open JSON object (metadata, an activity's content…) as an associative array.
     *
     * @return array<array-key, mixed>
     */
    public static function openObject(mixed $value, string $path): array
    {
        return array_map(self::value(...), self::object($value, $path));
    }

    /**
     * The JSON-ready form of an object: an object even when it is empty ({} rather than
     * []), or when its keys happen to be "0", "1"… which PHP stores as a list.
     *
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>|\stdClass
     */
    public static function encodeObject(array $value): array|\stdClass
    {
        return array_is_list($value) ? (object) $value : $value;
    }

    /**
     * Whether an optional property counts as set. Null does not: the protocol leaves an
     * unset property out, and a lenient reader takes a null from a producer that wrote one
     * anyway as absent (recording it), where a strict one rejects it.
     */
    public static function present(mixed $value, DecodeContext $context, string $path): bool
    {
        if (null !== $value) {
            return true;
        }

        if ($context->strict) {
            throw new InvalidShapeException($path, 'type', 'must not be null: leave the property out instead.');
        }

        $context->drop($path);

        return false;
    }

    public static function forbidNull(mixed $value, string $path): mixed
    {
        if (null === $value) {
            throw new InvalidShapeException($path, 'type', 'must not be null: leave the property out instead.');
        }

        return $value;
    }

    public static function pointer(string $path, string $segment): string
    {
        return $path.'/'.str_replace(['~', '/'], ['~0', '~1'], $segment);
    }

    private static function describe(mixed $value): string
    {
        return match (true) {
            null === $value => 'null',
            \is_bool($value) => 'a boolean',
            \is_int($value) => 'an integer',
            \is_float($value) => 'a number',
            \is_string($value) => 'a string',
            \is_array($value) && array_is_list($value) && [] !== $value => 'an array',
            \is_array($value), $value instanceof \stdClass => 'an object',
            default => get_debug_type($value),
        };
    }
}
