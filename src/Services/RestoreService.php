<?php

namespace Backup\Php\Services;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Services\Interface\RestoreServiceInterface;
use Backup\Php\Step\Runner\StepRunner;
use Backup\Php\Step\Interface\RestoreStep;
use Backup\Php\Step\Restore\Rollback\RestoreRollbackHandler;
use Throwable;

final class RestoreService implements RestoreServiceInterface
{
    /**
     * Summary of restore
     * @param array<RestoreStep> $steps
     */
    public function __construct(
        private readonly array $steps,
        private readonly StepRunner $runner,
        private readonly RestoreRollbackHandler $rollback,
    ) {}
    
    public function restore(RestoreContext $context): void
    {
        try {
            $this->runner->run(
                $this->steps,
                $context,
                static fn (RestoreStep $step, RestoreContext $context) => $step->execute($context),
            );
        } catch (Throwable $e) {
            $this->rollback->rollback($context);

            throw $e;
        }
    }
}