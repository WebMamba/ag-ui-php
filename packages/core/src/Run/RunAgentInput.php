<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A request to run an agent. Also echoed back as RUN_STARTED.input. Only threadId, runId and
 * messages are required: those are the three the SDKs already agree on, and for tools and
 * context an absent key and an empty array mean the same thing, so requiring them would catch
 * nothing a producer could get wrong.
 */
final class RunAgentInput implements Model
{
    /**
     * @param string $threadId the conversation this run belongs to
     * @param string $runId identifies this run
     * @param list<\AgUi\Message\Message> $messages the conversation so far, in order
     * @param string|null $protocolVersion The protocol version this consumer speaks, such as "1.0". Absent means the input was produced before the protocol carried a version — the versioning rules in the prose govern what each side does with that. Sent in-band rather than by the transport, so a recorded exchange stays self-describing.
     * @param string|null $parentRunId the run that spawned this one
     * @param mixed $state the state the run starts from
     * @param list<Tool>|null $tools The tools the agent may call. Absent means none.
     * @param list<Context>|null $context Ambient information for the run. Absent means none.
     * @param mixed $forwardedProps Application-specific values passed through to the agent untouched. Any JSON value.
     * @param list<ResumeEntry>|null $resume answers to the interrupts that ended a previous run, when this run continues from one
     */
    public function __construct(
        public private(set) string $threadId,
        public private(set) string $runId,
        #[Assert\Valid]
        public private(set) array $messages,
        public private(set) ?string $protocolVersion = null,
        public private(set) ?string $parentRunId = null,
        public private(set) mixed $state = null,
        #[Assert\Valid]
        public private(set) ?array $tools = null,
        #[Assert\Valid]
        public private(set) ?array $context = null,
        public private(set) mixed $forwardedProps = null,
        #[Assert\Valid]
        public private(set) ?array $resume = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['threadId', 'runId', 'messages', 'protocolVersion', 'parentRunId', 'state', 'tools', 'context', 'forwardedProps', 'resume'], $context, $path);

        return new self(
            threadId: Json::string(Json::required($properties, 'threadId', $path), Json::pointer($path, 'threadId')),
            runId: Json::string(Json::required($properties, 'runId', $path), Json::pointer($path, 'runId')),
            messages: array_values(array_filter(Json::list(Json::required($properties, 'messages', $path), Json::pointer($path, 'messages'), static fn (mixed $item, string $at) => \AgUi\Message\Messages::tryFromArray($item, $context, $at)))),
            protocolVersion: \array_key_exists('protocolVersion', $properties) && Json::present($properties['protocolVersion'], $context, Json::pointer($path, 'protocolVersion')) ? Json::string($properties['protocolVersion'], Json::pointer($path, 'protocolVersion')) : null,
            parentRunId: \array_key_exists('parentRunId', $properties) && Json::present($properties['parentRunId'], $context, Json::pointer($path, 'parentRunId')) ? Json::string($properties['parentRunId'], Json::pointer($path, 'parentRunId')) : null,
            state: \array_key_exists('state', $properties) && Json::present($properties['state'], $context, Json::pointer($path, 'state')) ? Json::value(Json::forbidNull($properties['state'], Json::pointer($path, 'state'))) : null,
            tools: \array_key_exists('tools', $properties) && Json::present($properties['tools'], $context, Json::pointer($path, 'tools')) ? Json::list($properties['tools'], Json::pointer($path, 'tools'), static fn (mixed $item, string $at) => Tool::fromArray(Json::object($item, $at), $context, $at)) : null,
            context: \array_key_exists('context', $properties) && Json::present($properties['context'], $context, Json::pointer($path, 'context')) ? Json::list($properties['context'], Json::pointer($path, 'context'), static fn (mixed $item, string $at) => Context::fromArray(Json::object($item, $at), $context, $at)) : null,
            forwardedProps: \array_key_exists('forwardedProps', $properties) && Json::present($properties['forwardedProps'], $context, Json::pointer($path, 'forwardedProps')) ? Json::value(Json::forbidNull($properties['forwardedProps'], Json::pointer($path, 'forwardedProps'))) : null,
            resume: \array_key_exists('resume', $properties) && Json::present($properties['resume'], $context, Json::pointer($path, 'resume')) ? Json::list($properties['resume'], Json::pointer($path, 'resume'), static fn (mixed $item, string $at) => ResumeEntry::fromArray(Json::object($item, $at), $context, $at)) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'threadId' => $this->threadId,
            'runId' => $this->runId,
            'messages' => array_map(static fn (\AgUi\Message\Message $item) => Json::encodeObject($item->toArray()), $this->messages),
        ];

        if (null !== $this->protocolVersion) {
            $data['protocolVersion'] = $this->protocolVersion;
        }

        if (null !== $this->parentRunId) {
            $data['parentRunId'] = $this->parentRunId;
        }

        if (null !== $this->state) {
            $data['state'] = $this->state;
        }

        if (null !== $this->tools) {
            $data['tools'] = array_map(static fn (Tool $item) => Json::encodeObject($item->toArray()), $this->tools);
        }

        if (null !== $this->context) {
            $data['context'] = array_map(static fn (Context $item) => Json::encodeObject($item->toArray()), $this->context);
        }

        if (null !== $this->forwardedProps) {
            $data['forwardedProps'] = $this->forwardedProps;
        }

        if (null !== $this->resume) {
            $data['resume'] = array_map(static fn (ResumeEntry $item) => Json::encodeObject($item->toArray()), $this->resume);
        }

        return $data;
    }
}
