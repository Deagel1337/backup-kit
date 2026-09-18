<?php

namespace Backup\Php\Services;

use Backup\Php\Context\BackupContext;
use Backup\Php\Services\Interface\BackupServiceInterface;
use Backup\Php\Step\Runner\StepRunner;
use Backup\Php\Step\Interface\BackupStep;

final class BackupService implements BackupServiceInterface
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