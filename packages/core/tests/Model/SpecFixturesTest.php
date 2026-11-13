<?php

declare(strict_types=1);

namespace AgUi\Tests\Model;

use AgUi\Exception\InvalidShapeException;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use AgUi\Tests\Spec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Holds every generated model class to the protocol's own per-type fixtures
 * (spec/1.0/fixtures): a valid instance decodes, validates and encodes back to the
 * same JSON; an invalid one is refused at the location the fixture names.
 */
final class SpecFixturesTest extends TestCase
{
    private const array NAMESPACES = ['Event', 'Message', 'Content', 'Run', 'Patch', 'Capabilities'];

    /** @return iterable<string, array{class-string<Model>, string}> */
    public static function validFixtures(): iterable
    {
        foreach (glob(Spec::path('spec-1.0/fixtures/*/valid/*.json')) as $file) {
            yield self::fixtureName($file) => [self::classOf($file), $file];
        }
    }

    /** @return iterable<string, array{class-string<Model>, string, ?array<string, string>}> */
    public static function invalidFixtures(): iterable
    {
        foreach (glob(Spec::path('spec-1.0/fixtures/*/invalid/*.json')) as $file) {
            if (str_ends_with($file, '.expect.json')) {
                continue;
            }
            $expect = substr($file, 0, -5).'.expect.json';

            yield self::fixtureName($file) => [self::classOf($file), $file, is_file($expect) ? json_decode((string) file_get_contents($expect), true) : null];
        }
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('validFixtures')]
    public function testAValidInstanceRoundTrips(string $class, string $file): void
    {
        $json = Spec::decode($file);

        $model = $class::fromArray($json, new DecodeContext(strict: true));

        self::assertCount(0, $violations = $this->validator()->validate($model), (string) $violations);
        self::assertSame(
            Spec::canonical($json),
            Spec::canonical(json_decode(json_encode(Json::encodeObject($model->toArray()), \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION), false)),
            'Encoding gives back exactly what was decoded.',
        );
    }

    /**
     * @param class-string<Model> $class
     * @param array<string, string>|null $expect
     */
    #[DataProvider('invalidFixtures')]
    public function testAnInvalidInstanceIsRefusedWhereTheFixtureSays(string $class, string $file, ?array $expect): void
    {
        try {
            $model = $class::fromArray(Spec::decode($file), new DecodeContext(strict: true));
        } catch (InvalidShapeException $e) {
            if (null !== $expect) {
                self::assertTrue(self::sameError($expect, $e->instanceLocation), \sprintf('Expected the error at "%s", got: %s', $expect['instanceLocation'], $e->getMessage()));
            }

            return;
        }

        $violations = $this->validator()->validate($model);
        self::assertGreaterThan(0, \count($violations), 'Decoded and validated without complaint.');

        if (null !== $expect) {
            self::assertContains($expect['instanceLocation'], self::pointers($violations), (string) $violations);
        }
    }

    /**
     * Where a oneOf fails is a matter of reporting style: one validator blames the
     * object that matched no member, another the discriminator inside it. Either is the
     * same error, so for those keywords an ancestor or descendant location is accepted.
     *
     * @param array<string, string> $expect
     */
    private static function sameError(array $expect, string $location): bool
    {
        if ($expect['instanceLocation'] === $location) {
            return true;
        }

        if (!\in_array($expect['keyword'], ['oneOf', 'const'], true)) {
            return false;
        }

        return str_starts_with($location.'/', $expect['instanceLocation'].'/') || str_starts_with($expect['instanceLocation'].'/', $location.'/');
    }

    /** @return list<string> */
    private static function pointers(ConstraintViolationListInterface $violations): array
    {
        $pointers = [];
        foreach ($violations as $violation) {
            // "usage[0].inputTokens" → "/usage/0/inputTokens"
            $path = preg_replace('/\[(\d+)\]/', '.$1', $violation->getPropertyPath());
            $pointers[] = '' === $path ? '' : '/'.str_replace('.', '/', $path);
        }

        return $pointers;
    }

    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    /** @return class-string<Model> */
    private static function classOf(string $file): string
    {
        $type = basename(\dirname($file, 2));
        foreach (self::NAMESPACES as $namespace) {
            if (class_exists($class = "AgUi\\{$namespace}\\{$type}")) {
                return $class;
            }
        }

        throw new \LogicException(\sprintf('No class for fixture type "%s".', $type));
    }

    private static function fixtureName(string $file): string
    {
        return basename(\dirname($file, 2)).'/'.basename(\dirname($file)).'/'.basename($file, '.json');
    }
}
