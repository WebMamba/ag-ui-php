<?php

declare(strict_types=1);

namespace AgUi\SymfonyAi\Interrupt;

/**
 * Finds the resumable tool behind a tool name.
 */
interface ResumableToolLocator
{
    public function get(string $toolName): ?ResumableTool;
}
