<?php

declare(strict_types=1);

namespace AgUi\Tests\Stream;

use AgUi\Event\Events;
use AgUi\Exception\ExceptionInterface;
use AgUi\Model\DecodeContext;
use AgUi\Stream\ChunkExpander;
use AgUi\Stream\EventSequenceVerifier;
use AgUi\Tests\Spec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * Replays the protocol's conformance streams (spec/1.0/conformance/streams) through the
 * same pipeline RunStream applies before the wire — decode, validate, expand chunks,
 * verify — and holds it to the client's verdict: a stream the official client rejects
 * is rejected here, and one it consumes is accepted.
 */
final class ConformanceStreamsTest extends TestCase
{
    /**
     * Fixtures about what a client does with a stream it accepts, which a producer-side
     * check has no opinion on. Each is listed with the reason, so the list only shrinks
     * deliberately.
     */
    private const array CLIENT_ONLY = [
        'era-0-0-45-thinking-translated' => 'Translating the retired THINKING_* events of old producers is a consumer concern: this SDK only ever produces REASONING_*.',
    ];

    /** @return iterable<string, array{string}> */
    public static function streams(): iterable
    {
        foreach (glob(Spec::path('spec-1.0/conformance/streams/*.json')) as $file) {
            yield basename($file, '.json') => [$file];
        }
    }

    #[DataProvider('streams')]
    public function testTheVerdictMatchesTheOfficialClient(string $file): void
    {
        $fixture = json_decode((string) file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);
        $name = basename($file, '.json');

        if (isset(self::CLIENT_ONLY[$name])) {
            self::markTestSkipped(self::CLIENT_ONLY[$name]);
        }

        $expect = $fixture['expect'];
        $error = null;

        try {
            $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
            $expander = new ChunkExpander();
            $verifier = new EventSequenceVerifier();

            foreach (json_decode(json_encode($fixture['stream']), false) as $index => $raw) {
                // An unknown event type, from a newer producer, is dropped as the client drops it.
                if (null === $event = Events::tryFromArray($raw, new DecodeContext(), '/stream/'.$index)) {
                    continue;
                }
                if (\count($violations = $validator->validate($event)) > 0) {
                    throw new \RuntimeException((string) $violations);
                }
                foreach ($expander->expand($event) as $expanded) {
                    $verifier->verify($expanded);
                }
            }
        } catch (ExceptionInterface|\RuntimeException $e) {
            $error = $e;
        }

        if ('failed' === $expect['outcome']) {
            self::assertNotNull($error, \sprintf('The client rejects this stream (%s), but it was accepted.', $expect['errorContains'] ?? $fixture['description']));
        } else {
            self::assertNull($error, \sprintf('The client consumes this stream (%s), but it was rejected: %s', $fixture['description'], $error?->getMessage()));
        }
    }
}
