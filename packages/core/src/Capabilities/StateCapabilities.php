<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * State and memory management capabilities. These tell the client how the agent handles shared
 * state and whether conversation context persists across runs.
 */
final class StateCapabilities implements Model
{
    /**
     * @param bool|null $snapshots set true if the agent emits STATE_SNAPSHOT events (full state replacement)
     * @param bool|null $deltas set true if the agent emits STATE_DELTA events (JSON Patch incremental updates)
     * @param bool|null $memory Set true if the agent has long-term memory beyond the current thread (e.g., vector store, knowledge base, or cross-session recall).
     * @param bool|null $persistentState Set true if state is preserved across multiple runs within the same thread. When false, state resets on each run.
     */
    public function __construct(
        public private(set) ?bool $snapshots = null,
        public private(set) ?bool $deltas = null,
        public private(set) ?bool $memory = null,
        public private(set) ?bool $persistentState = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['snapshots', 'deltas', 'memory', 'persistentState'], $context, $path);

        return new self(
            snapshots: \array_key_exists('snapshots', $properties) && Json::present($properties['snapshots'], $context, Json::pointer($path, 'snapshots')) ? Json::bool($properties['snapshots'], Json::pointer($path, 'snapshots')) : null,
            deltas: \array_key_exists('deltas', $properties) && Json::present($properties['deltas'], $context, Json::pointer($path, 'deltas')) ? Json::bool($properties['deltas'], Json::pointer($path, 'deltas')) : null,
            memory: \array_key_exists('memory', $properties) && Json::present($properties['memory'], $context, Json::pointer($path, 'memory')) ? Json::bool($properties['memory'], Json::pointer($path, 'memory')) : null,
            persistentState: \array_key_exists('persistentState', $properties) && Json::present($properties['persistentState'], $context, Json::pointer($path, 'persistentState')) ? Json::bool($properties['persistentState'], Json::pointer($path, 'persistentState')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->snapshots) {
            $data['snapshots'] = $this->snapshots;
        }

        if (null !== $this->deltas) {
            $data['deltas'] = $this->deltas;
        }

        if (null !== $this->memory) {
            $data['memory'] = $this->memory;
        }

        if (null !== $this->persistentState) {
            $data['persistentState'] = $this->persistentState;
        }

        return $data;
    }
}
