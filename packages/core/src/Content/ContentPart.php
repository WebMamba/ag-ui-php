<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Content;

use AgUi\Model\Model;

/**
 * One part of a message body: what a person sends in a user message, or what a tool returns in
 * a tool message. Discriminated by type. Named by what the part is rather than by direction,
 * because the same part travels into the model inside a user message and back out of the
 * stream inside a tool result.
 */
interface ContentPart extends Model
{
}
