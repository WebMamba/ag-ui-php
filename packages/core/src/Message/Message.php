<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Message;

use AgUi\Model\Attributable;
use AgUi\Model\Model;

/**
 * Any message in a conversation. Discriminated by role.
 */
interface Message extends Model, Attributable
{
    /** Identifies the message. */
    public string $id { get; }

    /** Who the message is from: the value of its "role" property. */
    public function role(): Role;
}
