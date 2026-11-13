<?php

// Generated from the AG-UI JSON Schema by tools/generate-model.php: do not edit by hand.

declare(strict_types=1);

namespace AgUi\Run;

use AgUi\Model\DecodeContext;
use AgUi\Model\Json;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The run is paused, waiting for something outside it. Resuming means starting a new run whose
 * resume entries answer these interrupts.
 */
final class RunFinishedInterruptOutcome implements RunFinishedOutcome
{
    /**
     * @param list<Interrupt> $interrupts What the run is waiting for. At least one: an interrupt outcome with nothing to answer would leave a consumer with nothing to do.
     */
    public function __construct(
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public private(set) array $interrupts,
    ) {
    }

    public static function fromArray(array|\stdClass $data, DecodeContext $context = new DecodeContext(), string $path = ''): static
    {
        $properties = Json::closed(Json::object($data, $path), ['type', 'interrupts'], $context, $path);
        Json::const(Json::required($properties, 'type', $path), 'interrupt', Json::pointer($path, 'type'));

        return new self(
            interrupts: Json::list(Json::required($properties, 'interrupts', $path), Json::pointer($path, 'interrupts'), static fn (mixed $item, string $at) => Interrupt::fromArray(Json::object($item, $at), $context, $at)),
        );
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'interrupt',
            'interrupts' => array_map(static fn (Interrupt $item) => Json::encodeObject($item->toArray()), $this->interrupts),
        ];

        return $data;
    }
}
