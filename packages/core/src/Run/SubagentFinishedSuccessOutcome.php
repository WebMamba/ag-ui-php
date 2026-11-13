<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * The subagent completed its work. Equivalent to an absent outcome.
 */
final class SubagentFinishedSuccessOutcome implements SubagentFinishedOutcome
{
    public function __construct()
    {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'success', Json::pointer($path, 'type'));

        return new self();
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'success',
        ];

        return $data;
    }
}
