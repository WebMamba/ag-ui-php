<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\Attributable;
use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Something a run needs from outside before it can continue, such as an approval or a missing
 * value.
 */
final class Interrupt implements Model, Attributable
{
    /**
     * @param string $id Identifies the interrupt. A resume entry answers it by this id.
     * @param string $reason Why the run stopped. An open string rather than an enumeration: the protocol does not attempt to classify every reason an agent might need input.
     * @param string|null $message a human-readable prompt for whoever answers
     * @param string|null $toolCallId the tool call this interrupt concerns, when it is a tool approval
     * @param array<array-key, mixed>|null $responseSchema A JSON Schema describing the answer this interrupt expects, so a consumer can build a form for it. Carried opaquely: the protocol does not constrain or validate it. Restricted to an object because TypeScript and Python both declare it that way; .NET holds it as any JSON, and the ticket that commissioned this schema lists it among the arbitrary-JSON fields. Following the two that constrain it keeps the schema from accepting documents the reference client rejects, at the cost of rejecting the boolean schemas JSON Schema also permits — a bare true for "any answer". Recorded as a known divergence rather than settled.
     * @param string|null $expiresAt When the interrupt stops being answerable. Deliberately unconstrained rather than a date-time format, because producers already disagree about the representation and tightening it here would reject streams that work today. The documented convention is ISO 8601, and a consumer comparing this value will parse it as a date, so a value that is not one leaves the interrupt looking permanently unexpired.
     * @param array<array-key, mixed>|null $metadata extra information attached to this interrupt
     * @param string|null $subagentRunId The subagent invocation this belongs to. Absent means the parent agent produced it directly.
     */
    public function __construct(
        public private(set) string $id,
        public private(set) string $reason,
        public private(set) ?string $message = null,
        public private(set) ?string $toolCallId = null,
        public private(set) ?array $responseSchema = null,
        public private(set) ?string $expiresAt = null,
        public private(set) ?array $metadata = null,
        public private(set) ?string $subagentRunId = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['id', 'reason', 'message', 'toolCallId', 'responseSchema', 'expiresAt', 'metadata', 'subagentRunId'], $context, $path);

        return new self(
            id: Json::string(Json::required($properties, 'id', $path), Json::pointer($path, 'id')),
            reason: Json::string(Json::required($properties, 'reason', $path), Json::pointer($path, 'reason')),
            message: \array_key_exists('message', $properties) && Json::present($properties['message'], $context, Json::pointer($path, 'message')) ? Json::string($properties['message'], Json::pointer($path, 'message')) : null,
            toolCallId: \array_key_exists('toolCallId', $properties) && Json::present($properties['toolCallId'], $context, Json::pointer($path, 'toolCallId')) ? Json::string($properties['toolCallId'], Json::pointer($path, 'toolCallId')) : null,
            responseSchema: \array_key_exists('responseSchema', $properties) && Json::present($properties['responseSchema'], $context, Json::pointer($path, 'responseSchema')) ? Json::openObject($properties['responseSchema'], Json::pointer($path, 'responseSchema')) : null,
            expiresAt: \array_key_exists('expiresAt', $properties) && Json::present($properties['expiresAt'], $context, Json::pointer($path, 'expiresAt')) ? Json::string($properties['expiresAt'], Json::pointer($path, 'expiresAt')) : null,
            metadata: \array_key_exists('metadata', $properties) && Json::present($properties['metadata'], $context, Json::pointer($path, 'metadata')) ? Json::openObject($properties['metadata'], Json::pointer($path, 'metadata')) : null,
            subagentRunId: \array_key_exists('subagentRunId', $properties) && Json::present($properties['subagentRunId'], $context, Json::pointer($path, 'subagentRunId')) ? Json::string($properties['subagentRunId'], Json::pointer($path, 'subagentRunId')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'reason' => $this->reason,
        ];

        if (null !== $this->message) {
            $data['message'] = $this->message;
        }

        if (null !== $this->toolCallId) {
            $data['toolCallId'] = $this->toolCallId;
        }

        if (null !== $this->responseSchema) {
            $data['responseSchema'] = Json::encodeObject($this->responseSchema);
        }

        if (null !== $this->expiresAt) {
            $data['expiresAt'] = $this->expiresAt;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = Json::encodeObject($this->metadata);
        }

        if (null !== $this->subagentRunId) {
            $data['subagentRunId'] = $this->subagentRunId;
        }

        return $data;
    }
}
