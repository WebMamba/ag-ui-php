<?php

declare(strict_types=1);

namespace AgUi\Patch;

/**
 * Computes the RFC 6902 operations that turn one JSON document into another, as a
 * STATE_DELTA or an ACTIVITY_DELTA carries them.
 *
 * Deliberately small: objects are diffed key by key, lists element by element with
 * additions appended and removals taken from the end. Not minimal for an insertion in
 * the middle of a list, but always correct.
 */
final class JsonPatch
{
    /** @return list<JsonPatchOperation> */
    public static function diff(mixed $from, mixed $to): array
    {
        return self::diffAt($from, $to, '');
    }

    /** @return list<JsonPatchOperation> */
    private static function diffAt(mixed $from, mixed $to, string $path): array
    {
        if ($from === $to) {
            return [];
        }

        if (\is_array($from) && \is_array($to) && ([] === $from || [] === $to || array_is_list($from) === array_is_list($to))) {
            return array_is_list($to) && array_is_list($from)
                ? self::diffList($from, $to, $path)
                : self::diffObject($from, $to, $path);
        }

        return [new ReplaceOperation($path, $to)];
    }

    /**
     * @param array<array-key, mixed> $from
     * @param array<array-key, mixed> $to
     *
     * @return list<JsonPatchOperation>
     */
    private static function diffObject(array $from, array $to, string $path): array
    {
        $operations = [];

        foreach ($from as $key => $value) {
            if (!\array_key_exists($key, $to)) {
                $operations[] = new RemoveOperation($path.'/'.self::escape((string) $key));
            }
        }

        foreach ($to as $key => $value) {
            $child = $path.'/'.self::escape((string) $key);
            $operations = \array_key_exists($key, $from)
                ? [...$operations, ...self::diffAt($from[$key], $value, $child)]
                : [...$operations, new AddOperation($child, $value)];
        }

        return $operations;
    }

    /**
     * @param list<mixed> $from
     * @param list<mixed> $to
     *
     * @return list<JsonPatchOperation>
     */
    private static function diffList(array $from, array $to, string $path): array
    {
        $operations = [];
        $common = min(\count($from), \count($to));

        for ($i = 0; $i < $common; ++$i) {
            $operations = [...$operations, ...self::diffAt($from[$i], $to[$i], $path.'/'.$i)];
        }

        // Remove from the end, so the earlier indices stay valid while the patch applies.
        for ($i = \count($from) - 1; $i >= $common; --$i) {
            $operations[] = new RemoveOperation($path.'/'.$i);
        }

        for ($i = $common, $n = \count($to); $i < $n; ++$i) {
            $operations[] = new AddOperation($path.'/-', $to[$i]);
        }

        return $operations;
    }

    private static function escape(string $key): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $key);
    }
}
