<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * The run was stopped before it completed, by whoever was running it, and did not fail.
 * Neither success nor interrupt: nothing was produced as a result, and nothing is waited for,
 * so the next run on the thread is an ordinary new run rather than a resume. Closed like its
 * siblings: a cancelled run has no interrupts to carry. Named in the schema before 1.0 because
 * an outcome a consumer does not recognise is stripped and read as success — a cancellation
 * added later would reach every 1.0 consumer as a completed run.
 */
final class RunFinishedCancelledOutcome implements RunFinishedOutcome
{
    public function __construct()
    {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'cancelled', Json::pointer($path, 'type'));

        return new self();
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'cancelled',
        ];

        return $data;
    }
}
