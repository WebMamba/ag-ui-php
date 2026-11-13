<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App;

use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Result\Stream\Delta\DeltaInterface;
use Symfony\AI\Platform\Result\StreamResult;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\AI\Platform\Tool\Tool;

/**
 * A platform whose model rounds the test scripts: each round is a list of deltas.
 */
final class ScriptedPlatform
{
    /** @var list<list<DeltaInterface>> */
    public static array $rounds = [];

    /** @var list<mixed> what the model was sent, round by round */
    public static array $inputs = [];

    /** @var list<list<string>> the names of the tools the model was offered, round by round */
    public static array $tools = [];

    public static function create(): Platform
    {
        return MockPlatformFactory::createPlatform(static function (mixed $model, mixed $input, array $options = []): StreamResult {
            self::$inputs[] = $input;
            self::$tools[] = array_map(static fn (mixed $tool): string => $tool instanceof Tool ? $tool->getName() : (string) ($tool['function']['name'] ?? $tool['name'] ?? ''), $options['tools'] ?? []);
            $deltas = array_shift(self::$rounds) ?? [];

            return new StreamResult((static fn (): \Generator => yield from $deltas)());
        });
    }
}
