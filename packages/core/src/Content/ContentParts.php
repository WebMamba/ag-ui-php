<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Exception\InvalidShapeException;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * Decodes any ContentPart, telling its kinds apart by "type".
 */
final class ContentParts
{
    /** @var array<string, class-string<ContentPart>> */
    public const array CLASSES = [
        'text' => TextPart::class,
        'image' => ImagePart::class,
        'audio' => AudioPart::class,
        'video' => VideoPart::class,
        'document' => DocumentPart::class,
    ];

    /**
     * @throws InvalidShapeException
     */
    public static function fromArray(mixed $data, DecodeContext $context = new DecodeContext(), string $path = ''): ContentPart
    {
        [$properties, $kind] = Json::discriminate($data, 'type', $path);

        $class = self::CLASSES[$kind] ?? throw new InvalidShapeException($path, 'oneOf', \sprintf('"%s" is not a known type.', $kind));

        return $class::fromArray($properties, $context, $path);
    }

    /**
     * Like fromArray(), except that a kind this SDK does not know, from a newer peer, is
     * dropped and recorded rather than rejected, when the context is lenient: what the
     * protocol asks of a consumer for a member of a list or an optional property.
     *
     * @throws InvalidShapeException
     */
    public static function tryFromArray(mixed $data, DecodeContext $context = new DecodeContext(), string $path = ''): ?ContentPart
    {
        [$properties, $kind] = Json::discriminate($data, 'type', $path);

        if (!isset(self::CLASSES[$kind]) && !$context->strict) {
            $context->drop($path);

            return null;
        }

        return self::fromArray($properties, $context, $path);
    }
}
