<?php

declare(strict_types=1);

namespace AgUi\Tests\Encoder;

use AgUi\Encoder\SseEncoder;
use AgUi\Event\Events;
use AgUi\Tests\Spec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The cross-SDK contract that a producer leaves an unset property out of the JSON rather
 * than writing null, while a null that is part of a value (inside state, a custom
 * event's value, a patch) is kept (sdks/fixtures/null-omission.json).
 */
final class NullOmissionTest extends TestCase
{
    /** @return iterable<string, array{mixed, mixed}> */
    public static function cases(): iterable
    {
        $fixture = Spec::decode(Spec::path('sdk-fixtures/null-omission.json'));

        foreach ($fixture->stream as $case) {
            yield $case->name => [$case->input, $case->expected];
        }
    }

    #[DataProvider('cases')]
    public function testUnsetPropertiesAreLeftOut(mixed $input, mixed $expected): void
    {
        $frame = (new SseEncoder())->encode(Events::fromArray($input));

        self::assertStringStartsWith('data: ', $frame);
        self::assertStringEndsWith("\n\n", $frame);
        self::assertSame(Spec::canonical($expected), Spec::canonical(json_decode(substr($frame, 6), false, 512, \JSON_THROW_ON_ERROR)));
    }
}
