<?php

declare(strict_types=1);

namespace AgUi\Model;

use AgUi\Exception\InvalidShapeException;

/**
 * A protocol type, generated from the AG-UI JSON Schema: it decodes itself from
 * decoded JSON and encodes itself back, exactly as the schema describes it.
 */
interface Model
{
    /**
     * @param array<mixed>|\stdClass $data decoded JSON (associative arrays or objects)
     *
     * @throws InvalidShapeException
     */
    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static;

    /**
     * The JSON-ready value: absent optional properties are left out, never written as
     * null, and JSON objects that are empty are \stdClass so they encode as {}.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
