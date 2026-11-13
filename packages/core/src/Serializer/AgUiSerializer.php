<?php

declare(strict_types=1);

namespace AgUi\Serializer;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * A Serializer set up for the AG-UI wire format, for applications that do not have one
 * configured already (the Symfony bundle registers ModelNormalizer in the app's own).
 */
final class AgUiSerializer
{
    /** JSON as the protocol writes it: no escaped slashes or Unicode, 1.0 stays 1.0. */
    public const int JSON_ENCODE_OPTIONS = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION;

    public static function create(): SerializerInterface
    {
        return new Serializer(
            [new ModelNormalizer()],
            [new JsonEncoder(new JsonEncode([JsonEncode::OPTIONS => self::JSON_ENCODE_OPTIONS]), new JsonDecode([JsonDecode::ASSOCIATIVE => true]))],
        );
    }
}
