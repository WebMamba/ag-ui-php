<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Execution control and limits. Declare these so clients can set expectations about how long
 * or how many steps an agent run might take.
 */
final class ExecutionCapabilities implements Model
{
    /**
     * @param bool|null $codeExecution Set true if the agent can execute code (e.g., Python, JavaScript) during a run.
     * @param bool|null $sandboxed Set true if code execution happens in a sandboxed or isolated environment. Only meaningful when codeExecution is true.
     * @param int|null $maxIterations Maximum number of tool-call/reasoning iterations the agent will perform per run. Helps clients display progress or set timeout expectations.
     * @param int|null $maxExecutionTime maximum wall-clock time (in milliseconds) the agent will run before timing out
     */
    public function __construct(
        public private(set) ?bool $codeExecution = null,
        public private(set) ?bool $sandboxed = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $maxIterations = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $maxExecutionTime = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['codeExecution', 'sandboxed', 'maxIterations', 'maxExecutionTime'], $context, $path);

        return new self(
            codeExecution: \array_key_exists('codeExecution', $properties) && Json::present($properties['codeExecution'], $context, Json::pointer($path, 'codeExecution')) ? Json::bool($properties['codeExecution'], Json::pointer($path, 'codeExecution')) : null,
            sandboxed: \array_key_exists('sandboxed', $properties) && Json::present($properties['sandboxed'], $context, Json::pointer($path, 'sandboxed')) ? Json::bool($properties['sandboxed'], Json::pointer($path, 'sandboxed')) : null,
            maxIterations: \array_key_exists('maxIterations', $properties) && Json::present($properties['maxIterations'], $context, Json::pointer($path, 'maxIterations')) ? Json::int($properties['maxIterations'], Json::pointer($path, 'maxIterations')) : null,
            maxExecutionTime: \array_key_exists('maxExecutionTime', $properties) && Json::present($properties['maxExecutionTime'], $context, Json::pointer($path, 'maxExecutionTime')) ? Json::int($properties['maxExecutionTime'], Json::pointer($path, 'maxExecutionTime')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->codeExecution) {
            $data['codeExecution'] = $this->codeExecution;
        }

        if (null !== $this->sandboxed) {
            $data['sandboxed'] = $this->sandboxed;
        }

        if (null !== $this->maxIterations) {
            $data['maxIterations'] = $this->maxIterations;
        }

        if (null !== $this->maxExecutionTime) {
            $data['maxExecutionTime'] = $this->maxExecutionTime;
        }

        return $data;
    }
}
