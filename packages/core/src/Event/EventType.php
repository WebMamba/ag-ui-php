<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

/**
 * The discriminator carried by every event.
 */
enum EventType: string
{
    case TextMessageStart = 'TEXT_MESSAGE_START';
    case TextMessageContent = 'TEXT_MESSAGE_CONTENT';
    case TextMessageEnd = 'TEXT_MESSAGE_END';
    case TextMessageChunk = 'TEXT_MESSAGE_CHUNK';
    case ToolCallStart = 'TOOL_CALL_START';
    case ToolCallArgs = 'TOOL_CALL_ARGS';
    case ToolCallEnd = 'TOOL_CALL_END';
    case ToolCallChunk = 'TOOL_CALL_CHUNK';
    case ToolCallResult = 'TOOL_CALL_RESULT';
    case StateSnapshot = 'STATE_SNAPSHOT';
    case StateDelta = 'STATE_DELTA';
    case MessagesSnapshot = 'MESSAGES_SNAPSHOT';
    case ActivitySnapshot = 'ACTIVITY_SNAPSHOT';
    case ActivityDelta = 'ACTIVITY_DELTA';
    case Raw = 'RAW';
    case Custom = 'CUSTOM';
    case RunStarted = 'RUN_STARTED';
    case RunFinished = 'RUN_FINISHED';
    case RunError = 'RUN_ERROR';
    case StepStarted = 'STEP_STARTED';
    case StepFinished = 'STEP_FINISHED';
    case ReasoningStart = 'REASONING_START';
    case ReasoningMessageStart = 'REASONING_MESSAGE_START';
    case ReasoningMessageContent = 'REASONING_MESSAGE_CONTENT';
    case ReasoningMessageEnd = 'REASONING_MESSAGE_END';
    case ReasoningMessageChunk = 'REASONING_MESSAGE_CHUNK';
    case ReasoningEnd = 'REASONING_END';
    case ReasoningEncryptedValue = 'REASONING_ENCRYPTED_VALUE';
    case SubagentStarted = 'SUBAGENT_STARTED';
    case SubagentFinished = 'SUBAGENT_FINISHED';
    case SubagentError = 'SUBAGENT_ERROR';
}
