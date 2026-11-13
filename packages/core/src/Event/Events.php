<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

use AgUi\Exception\InvalidShapeException;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;

/**
 * Decodes any Event, telling its kinds apart by "type".
 */
final class Events
{
    /** @var array<string, class-string<Event>> */
    public const array CLASSES = [
        'TEXT_MESSAGE_START' => TextMessageStartEvent::class,
        'TEXT_MESSAGE_CONTENT' => TextMessageContentEvent::class,
        'TEXT_MESSAGE_END' => TextMessageEndEvent::class,
        'TEXT_MESSAGE_CHUNK' => TextMessageChunkEvent::class,
        'TOOL_CALL_START' => ToolCallStartEvent::class,
        'TOOL_CALL_ARGS' => ToolCallArgsEvent::class,
        'TOOL_CALL_END' => ToolCallEndEvent::class,
        'TOOL_CALL_CHUNK' => ToolCallChunkEvent::class,
        'TOOL_CALL_RESULT' => ToolCallResultEvent::class,
        'STATE_SNAPSHOT' => StateSnapshotEvent::class,
        'STATE_DELTA' => StateDeltaEvent::class,
        'MESSAGES_SNAPSHOT' => MessagesSnapshotEvent::class,
        'ACTIVITY_SNAPSHOT' => ActivitySnapshotEvent::class,
        'ACTIVITY_DELTA' => ActivityDeltaEvent::class,
        'RAW' => RawEvent::class,
        'CUSTOM' => CustomEvent::class,
        'RUN_STARTED' => RunStartedEvent::class,
        'RUN_FINISHED' => RunFinishedEvent::class,
        'RUN_ERROR' => RunErrorEvent::class,
        'STEP_STARTED' => StepStartedEvent::class,
        'STEP_FINISHED' => StepFinishedEvent::class,
        'REASONING_START' => ReasoningStartEvent::class,
        'REASONING_MESSAGE_START' => ReasoningMessageStartEvent::class,
        'REASONING_MESSAGE_CONTENT' => ReasoningMessageContentEvent::class,
        'REASONING_MESSAGE_END' => ReasoningMessageEndEvent::class,
        'REASONING_MESSAGE_CHUNK' => ReasoningMessageChunkEvent::class,
        'REASONING_END' => ReasoningEndEvent::class,
        'REASONING_ENCRYPTED_VALUE' => ReasoningEncryptedValueEvent::class,
        'SUBAGENT_STARTED' => SubagentStartedEvent::class,
        'SUBAGENT_FINISHED' => SubagentFinishedEvent::class,
        'SUBAGENT_ERROR' => SubagentErrorEvent::class,
    ];

    /**
     * @throws InvalidShapeException
     */
    public static function fromArray(mixed $data, DecodeContext $context = new DecodeContext(), string $path = ''): Event
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
    public static function tryFromArray(mixed $data, DecodeContext $context = new DecodeContext(), string $path = ''): ?Event
    {
        [$properties, $kind] = Json::discriminate($data, 'type', $path);

        if (!isset(self::CLASSES[$kind]) && !$context->strict) {
            $context->drop($path);

            return null;
        }

        return self::fromArray($properties, $context, $path);
    }
}
