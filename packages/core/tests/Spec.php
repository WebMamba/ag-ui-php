<?php

declare(strict_types=1);

namespace AgUi\Tests;

/**
 * Where the frozen AG-UI specification lives, and how to read it.
 */
final class Spec
{
    public static function path(string $relative = ''): string
    {
        return \dirname(__DIR__, 3).'/resources/ag-ui/'.$relative;
    }

    /**
     * Decodes JSON keeping objects as \stdClass, so {} and [] stay distinguishable.
     */
    public static function decode(string $file): mixed
    {
        return json_decode((string) file_get_contents($file), false, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * A canonical form for comparing two JSON documents regardless of key order.
     */
    public static function canonical(mixed $json): string
    {
        $sort = static function (mixed $value) use (&$sort): mixed {
            if ($value instanceof \stdClass) {
                $properties = get_object_vars($value);
                ksort($properties);

                return (object) array_map($sort, $properties);
            }

            return \is_array($value) ? array_map($sort, $value) : $value;
        };

        return json_encode($sort($json), \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }
}
