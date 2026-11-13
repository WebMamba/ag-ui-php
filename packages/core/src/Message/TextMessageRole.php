<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

/**
 * The roles a streamed text message may take. Excludes tool, which is carried by
 * TOOL_CALL_RESULT rather than streamed as text.
 */
enum TextMessageRole: string
{
    case Developer = 'developer';
    case System = 'system';
    case Assistant = 'assistant';
    case User = 'user';
}
