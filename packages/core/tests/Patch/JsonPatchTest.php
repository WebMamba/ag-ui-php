<?php

declare(strict_types=1);

namespace AgUi\Tests\Patch;

use AgUi\Patch\JsonPatch;
use AgUi\Patch\JsonPatchOperation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JsonPatchTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $expected
     */
    #[DataProvider('documents')]
    public function testItDiffsTwoDocuments(mixed $from, mixed $to, array $expected): void
    {
        self::assertSame($expected, array_map(static fn (JsonPatchOperation $op): array => $op->toArray(), JsonPatch::diff($from, $to)));
    }

    /** @return iterable<string, array{mixed, mixed, list<array<string, mixed>>}> */
    public static function documents(): iterable
    {
        yield 'identical' => [['a' => 1], ['a' => 1], []];
        yield 'changed scalar' => [['a' => 1], ['a' => 2], [['op' => 'replace', 'path' => '/a', 'value' => 2]]];
        yield 'added and removed keys' => [['a' => 1], ['b' => 2], [['op' => 'remove', 'path' => '/a'], ['op' => 'add', 'path' => '/b', 'value' => 2]]];
        yield 'appended to a list' => [['l' => [1]], ['l' => [1, 2]], [['op' => 'add', 'path' => '/l/-', 'value' => 2]]];
        yield 'shrunk a list from the end' => [['l' => [1, 2, 3]], ['l' => [1]], [['op' => 'remove', 'path' => '/l/2'], ['op' => 'remove', 'path' => '/l/1']]];
        yield 'escaped keys' => [[], ['a/b~c' => true], [['op' => 'add', 'path' => '/a~1b~0c', 'value' => true]]];
        yield 'type change' => [['a' => [1]], ['a' => 'x'], [['op' => 'replace', 'path' => '/a', 'value' => 'x']]];
        yield 'whole document' => [1, 2, [['op' => 'replace', 'path' => '', 'value' => 2]]];
    }
}
