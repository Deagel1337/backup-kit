<?php

namespace Src\Services;

use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;
use Restore\Runner\StepRunner;

final class BackupService 
{
    /**
     * Summary of restore
     * @param array<BackupStep> $steps
     */
    public function __construct(
        private readonly array $steps,
        private readonly StepRunner $runner,
    )
    {}

    public function backup(BackupContext $context): void
    {
        $this->runner->run(
            $this->steps,
            $context,
            static fn (BackupStep $step, BackupContext $context) => $step->execute(($context),)
        );
    }
}