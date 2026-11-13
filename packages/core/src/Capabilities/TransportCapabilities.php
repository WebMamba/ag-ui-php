<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Capabilities;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;

/**
 * Declares which transport mechanisms the agent supports. Clients use this to pick the best
 * connection strategy. Only set flags to true for transports your agent actually handles —
 * omit or set false for unsupported ones.
 */
final class TransportCapabilities implements Model
{
    /**
     * @param bool|null $streaming Set true if the agent streams responses via SSE. Most agents enable this.
     * @param bool|null $websocket set true if the agent accepts persistent WebSocket connections
     * @param bool|null $httpBinary set true if the agent supports the AG-UI binary protocol (protobuf over HTTP)
     * @param bool|null $pushNotifications set true if the agent can send async updates via webhooks after a run finishes
     * @param bool|null $resumable set true if the agent supports resuming interrupted streams via sequence numbers
     */
    public function __construct(
        public private(set) ?bool $streaming = null,
        public private(set) ?bool $websocket = null,
        public private(set) ?bool $httpBinary = null,
        public private(set) ?bool $pushNotifications = null,
        public private(set) ?bool $resumable = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['streaming', 'websocket', 'httpBinary', 'pushNotifications', 'resumable'], $context, $path);

        return new self(
            streaming: \array_key_exists('streaming', $properties) && Json::present($properties['streaming'], $context, Json::pointer($path, 'streaming')) ? Json::bool($properties['streaming'], Json::pointer($path, 'streaming')) : null,
            websocket: \array_key_exists('websocket', $properties) && Json::present($properties['websocket'], $context, Json::pointer($path, 'websocket')) ? Json::bool($properties['websocket'], Json::pointer($path, 'websocket')) : null,
            httpBinary: \array_key_exists('httpBinary', $properties) && Json::present($properties['httpBinary'], $context, Json::pointer($path, 'httpBinary')) ? Json::bool($properties['httpBinary'], Json::pointer($path, 'httpBinary')) : null,
            pushNotifications: \array_key_exists('pushNotifications', $properties) && Json::present($properties['pushNotifications'], $context, Json::pointer($path, 'pushNotifications')) ? Json::bool($properties['pushNotifications'], Json::pointer($path, 'pushNotifications')) : null,
            resumable: \array_key_exists('resumable', $properties) && Json::present($properties['resumable'], $context, Json::pointer($path, 'resumable')) ? Json::bool($properties['resumable'], Json::pointer($path, 'resumable')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->streaming) {
            $data['streaming'] = $this->streaming;
        }

        if (null !== $this->websocket) {
            $data['websocket'] = $this->websocket;
        }

        if (null !== $this->httpBinary) {
            $data['httpBinary'] = $this->httpBinary;
        }

        if (null !== $this->pushNotifications) {
            $data['pushNotifications'] = $this->pushNotifications;
        }

        if (null !== $this->resumable) {
            $data['resumable'] = $this->resumable;
        }

        return $data;
    }
}
