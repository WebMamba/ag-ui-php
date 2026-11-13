<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * The run completed. Equivalent to an absent outcome. Closed like every other object, which is
 * also what keeps it from carrying the suspended sibling's interrupts — a success with an
 * interrupt still pending would be a contradiction, not an extension. A completed run may
 * still have left frontend tool calls for the application to answer; pendingToolCallIds names
 * them.
 */
final class RunFinishedSuccessOutcome implements RunFinishedOutcome
{
    /**
     * @param list<string>|null $pendingToolCallIds The tool calls this run started and left unanswered — no TOOL_CALL_RESULT in the run — for the application to answer in the next input's messages, in the order they were made. Absent or empty means the producer named none, and a consumer derives the list from the stream; otherwise it is the list, and it agrees with the stream. On the success outcome rather than the event because a run that stopped on a frontend tool call is a completed run: whether the application continues the thread is its own decision, so the producer reports what it knows and no more.
     */
    public function __construct(
        public private(set) ?array $pendingToolCallIds = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'pendingToolCallIds'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'success', Json::pointer($path, 'type'));

        return new self(
            pendingToolCallIds: \array_key_exists('pendingToolCallIds', $properties) && Json::present($properties['pendingToolCallIds'], $context, Json::pointer($path, 'pendingToolCallIds')) ? Json::list($properties['pendingToolCallIds'], Json::pointer($path, 'pendingToolCallIds'), static fn (mixed $item, string $at) => Json::string($item, $at)) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'success',
        ];

        if (null !== $this->pendingToolCallIds) {
            $data['pendingToolCallIds'] = $this->pendingToolCallIds;
        }

        return $data;
    }
}
