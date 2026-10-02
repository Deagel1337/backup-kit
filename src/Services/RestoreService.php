<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Services\Interface\RestoreServiceInterface;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\RestoreRollbackHandler;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use Throwable;

final class RestoreService implements RestoreServiceInterface
{
    /**
     * Summary of restore
     *
     * @param  array<RestoreStep>  $steps
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
