<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * The subagent is paused awaiting outside input. Terminal for this stream, not for the
 * subagent: a later run may continue the same invocation once the interrupts are answered.
 */
final class SubagentFinishedSuspendedOutcome implements SubagentFinishedOutcome
{
    /**
     * @param list<string>|null $interruptIds The run-level interrupts this subagent raised itself. May be empty or absent: a subagent suspended because a descendant interrupted owns no interrupt of its own.
     */
    public function __construct(
        public private(set) ?array $interruptIds = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'interruptIds'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'suspended', Json::pointer($path, 'type'));

        return new self(
            interruptIds: \array_key_exists('interruptIds', $properties) && Json::present($properties['interruptIds'], $context, Json::pointer($path, 'interruptIds')) ? Json::list($properties['interruptIds'], Json::pointer($path, 'interruptIds'), static fn (mixed $item, string $at) => Json::string($item, $at)) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'suspended',
        ];

        if (null !== $this->interruptIds) {
            $data['interruptIds'] = $this->interruptIds;
        }

        return $data;
    }
}
