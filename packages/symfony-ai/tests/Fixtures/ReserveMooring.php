<?php

declare(strict_types=1);

namespace AgUi\SymfonyAi\Tests\Fixtures;

use AgUi\Run\ResumeEntry;
use AgUi\Run\ResumeStatus;
use AgUi\SymfonyAi\Activity\Activity;
use AgUi\SymfonyAi\Interrupt\Interrupt;
use AgUi\SymfonyAi\Interrupt\ResumableTool;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('reserve_mooring', 'Reserves a mooring.')]
final class ReserveMooring implements ResumableTool
{
    public function __invoke(string $marina): Interrupt
    {
        return Interrupt::confirmation("Reserve $marina?")->withActivity(new Activity('Asked to confirm.', 'confirm', ['marina' => $marina]));
    }

    public function resume(array $arguments, ResumeEntry $resume): mixed
    {
        $approved = ResumeStatus::Resolved === $resume->status && \is_array($resume->payload) && true === ($resume->payload['approved'] ?? false);

        return $approved ? 'Reserved at '.$arguments['marina'].'.' : 'Declined: nothing was reserved.';
    }
}
