<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use AgUi\Model\Model;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Token counts for one provider and model, in the protocol's own accounting: every count is
 * either a total or a named part of one, so entries from different providers add up without
 * double-counting. inputTokens and outputTokens are the totals; reasoningTokens,
 * cachedInputTokens and cacheWriteInputTokens are parts of them, never additions to them;
 * totalTokens is the two totals summed. Every field is a label or a number — nothing
 * content-bearing or identifying, no prompts, completions, messages, or thread, run and user
 * identifiers.
 */
final class TokenUsage implements Model
{
    /**
     * @param string|null $provider which provider served the request
     * @param string|null $model which model served the request
     * @param int|null $inputTokens Every prompt token the call was charged for: tokens read from a provider cache, tokens written to one, and audio or other non-text input all count here. cachedInputTokens and cacheWriteInputTokens break this number down and are never added to it — a provider that reports its cache counts beside a smaller input count has them added in by the producer before the entry leaves. Bounded like timestamp and for the same reason: a count above the JSON safe-integer range does not survive a round trip, so a consumer would silently read a different number than the producer wrote.
     * @param int|null $outputTokens Every generated token, reasoning included where the provider distinguishes it. reasoningTokens breaks this number down and is never added to it — a provider that reports reasoning tokens beside a smaller completion count has them added in by the producer.
     * @param int|null $totalTokens inputTokens plus outputTokens, under the accounting above. A producer MAY compute it rather than copy a provider's total, and copies a provider's total only when that total counts the same way, so a consumer can read this field as the sum of the other two.
     * @param int|null $reasoningTokens Output tokens spent on reasoning, where the provider distinguishes them. Part of outputTokens, not in addition to it.
     * @param int|null $cachedInputTokens Input tokens read from a provider cache. Part of inputTokens, not in addition to it, and disjoint from cacheWriteInputTokens.
     * @param int|null $cacheWriteInputTokens Input tokens written to a provider cache on this call, where the provider distinguishes them. Part of inputTokens, not in addition to it, and disjoint from cachedInputTokens. Its own field because providers price a cache write differently from a cache read, so a consumer computing cost cannot do without it.
     */
    public function __construct(
        public private(set) ?string $provider = null,
        public private(set) ?string $model = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $inputTokens = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $outputTokens = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $totalTokens = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $reasoningTokens = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $cachedInputTokens = null,
        #[Assert\Range(min: 0, max: 9007199254740991)]
        public private(set) ?int $cacheWriteInputTokens = null,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['provider', 'model', 'inputTokens', 'outputTokens', 'totalTokens', 'reasoningTokens', 'cachedInputTokens', 'cacheWriteInputTokens'], $context, $path);

        return new self(
            provider: \array_key_exists('provider', $properties) && Json::present($properties['provider'], $context, Json::pointer($path, 'provider')) ? Json::string($properties['provider'], Json::pointer($path, 'provider')) : null,
            model: \array_key_exists('model', $properties) && Json::present($properties['model'], $context, Json::pointer($path, 'model')) ? Json::string($properties['model'], Json::pointer($path, 'model')) : null,
            inputTokens: \array_key_exists('inputTokens', $properties) && Json::present($properties['inputTokens'], $context, Json::pointer($path, 'inputTokens')) ? Json::int($properties['inputTokens'], Json::pointer($path, 'inputTokens')) : null,
            outputTokens: \array_key_exists('outputTokens', $properties) && Json::present($properties['outputTokens'], $context, Json::pointer($path, 'outputTokens')) ? Json::int($properties['outputTokens'], Json::pointer($path, 'outputTokens')) : null,
            totalTokens: \array_key_exists('totalTokens', $properties) && Json::present($properties['totalTokens'], $context, Json::pointer($path, 'totalTokens')) ? Json::int($properties['totalTokens'], Json::pointer($path, 'totalTokens')) : null,
            reasoningTokens: \array_key_exists('reasoningTokens', $properties) && Json::present($properties['reasoningTokens'], $context, Json::pointer($path, 'reasoningTokens')) ? Json::int($properties['reasoningTokens'], Json::pointer($path, 'reasoningTokens')) : null,
            cachedInputTokens: \array_key_exists('cachedInputTokens', $properties) && Json::present($properties['cachedInputTokens'], $context, Json::pointer($path, 'cachedInputTokens')) ? Json::int($properties['cachedInputTokens'], Json::pointer($path, 'cachedInputTokens')) : null,
            cacheWriteInputTokens: \array_key_exists('cacheWriteInputTokens', $properties) && Json::present($properties['cacheWriteInputTokens'], $context, Json::pointer($path, 'cacheWriteInputTokens')) ? Json::int($properties['cacheWriteInputTokens'], Json::pointer($path, 'cacheWriteInputTokens')) : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if (null !== $this->provider) {
            $data['provider'] = $this->provider;
        }

        if (null !== $this->model) {
            $data['model'] = $this->model;
        }

        if (null !== $this->inputTokens) {
            $data['inputTokens'] = $this->inputTokens;
        }

        if (null !== $this->outputTokens) {
            $data['outputTokens'] = $this->outputTokens;
        }

        if (null !== $this->totalTokens) {
            $data['totalTokens'] = $this->totalTokens;
        }

        if (null !== $this->reasoningTokens) {
            $data['reasoningTokens'] = $this->reasoningTokens;
        }

        if (null !== $this->cachedInputTokens) {
            $data['cachedInputTokens'] = $this->cachedInputTokens;
        }

        if (null !== $this->cacheWriteInputTokens) {
            $data['cacheWriteInputTokens'] = $this->cacheWriteInputTokens;
        }

        return $data;
    }
}
