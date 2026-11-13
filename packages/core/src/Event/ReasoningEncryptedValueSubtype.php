<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Event;

/**
 * Whether a REASONING_ENCRYPTED_VALUE belongs to a message or to a tool call.
 */
enum ReasoningEncryptedValueSubtype: string
{
    case ToolCall = 'tool-call';
    case Message = 'message';
}
