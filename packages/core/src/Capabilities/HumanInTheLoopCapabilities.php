<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Human-in-the-loop interaction support. Enable these when your agent can pause execution to
 * request human input, approval, or feedback before continuing.
 */
final class HumanInTheLoopCapabilities implements Model
{
    /**
     * @param bool|null $supported set true if the agent supports any form of human-in-the-loop interaction
     * @param bool|null $approvals Set true if the agent can pause and request explicit approval before performing sensitive actions (e.g., sending emails, deleting data).
     * @param bool|null $interventions set true if the agent allows humans to intervene and modify its plan mid-execution
     * @param bool|null $feedback set true if the agent can incorporate user feedback (thumbs up/down, corrections) to improve its behavior within the current session
     * @param bool|null $interrupts Set true if the agent participates in the AG-UI interrupt protocol: it ends a run with RUN_FINISHED carrying an interrupt outcome, and accepts the answers back in RunAgentInput.resume.
     * @param bool|null $approveWithEdits Set true if tool-call interrupts accept editedArgs in the resume payload. Only meaningful when interrupts is true.
     */
    public function __construct(
        public private(set) ?bool $supported = null,
        public private(set) ?bool $approvals = null,
        public private(set) ?bool $interventions = null,
        public private(set) ?bool $feedback = null,
        public private(set) ?bool $interrupts = null,
        public private(set) ?bool $approveWithEdits = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['supported', 'approvals', 'interventions', 'feedback', 'interrupts', 'approveWithEdits'], $context, $path);

        return new self(
            supported: \array_key_exists('supported', $properties) && Json::present($properties['supported'], $context, Json::pointer($path, 'supported')) ? Json::bool($properties['supported'], Json::pointer($path, 'supported')) : null,
            approvals: \array_key_exists('approvals', $properties) && Json::present($properties['approvals'], $context, Json::pointer($path, 'approvals')) ? Json::bool($properties['approvals'], Json::pointer($path, 'approvals')) : null,
            interventions: \array_key_exists('interventions', $properties) && Json::present($properties['interventions'], $context, Json::pointer($path, 'interventions')) ? Json::bool($properties['interventions'], Json::pointer($path, 'interventions')) : null,
            feedback: \array_key_exists('feedback', $properties) && Json::present($properties['feedback'], $context, Json::pointer($path, 'feedback')) ? Json::bool($properties['feedback'], Json::pointer($path, 'feedback')) : null,
            interrupts: \array_key_exists('interrupts', $properties) && Json::present($properties['interrupts'], $context, Json::pointer($path, 'interrupts')) ? Json::bool($properties['interrupts'], Json::pointer($path, 'interrupts')) : null,
            approveWithEdits: \array_key_exists('approveWithEdits', $properties) && Json::present($properties['approveWithEdits'], $context, Json::pointer($path, 'approveWithEdits')) ? Json::bool($properties['approveWithEdits'], Json::pointer($path, 'approveWithEdits')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->supported) {
            $data['supported'] = $this->supported;
        }

        if (null !== $this->approvals) {
            $data['approvals'] = $this->approvals;
        }

        if (null !== $this->interventions) {
            $data['interventions'] = $this->interventions;
        }

        if (null !== $this->feedback) {
            $data['feedback'] = $this->feedback;
        }

        if (null !== $this->interrupts) {
            $data['interrupts'] = $this->interrupts;
        }

        if (null !== $this->approveWithEdits) {
            $data['approveWithEdits'] = $this->approveWithEdits;
        }

        return $data;
    }
}
