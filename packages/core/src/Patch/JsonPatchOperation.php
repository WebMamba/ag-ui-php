<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Patch;

use AgUi\Model\Model;

/**
 * A single RFC 6902 operation. Exactly one of the operation shapes must match, discriminated
 * by op. Unlike the protocol's own objects, the operations are open: RFC 6902 section 4
 * requires members an operation does not define to be ignored rather than rejected, so a
 * remove carrying a leftover value is a valid patch. Two of the RFC's rules are relations
 * between values rather than shapes, so no static schema can express them and neither is
 * checked here: a move whose from is a proper prefix of its path (section 4.4), and any
 * operation whose pointer does not resolve in the target document. Both are the applier's to
 * reject.
 */
interface JsonPatchOperation extends Model
{
}
