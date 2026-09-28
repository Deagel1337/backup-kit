<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Services\Interface\BackupServiceInterface;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;

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