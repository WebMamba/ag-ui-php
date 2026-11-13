<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

/**
 * Every role a materialised message may have.
 */
enum Role: string
{
    case Developer = 'developer';
    case System = 'system';
    case Assistant = 'assistant';
    case User = 'user';
    case Tool = 'tool';
    case Activity = 'activity';
    case Reasoning = 'reasoning';
}
