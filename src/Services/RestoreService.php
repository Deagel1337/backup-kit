<?php

namespace Src\Services;

use Restore\Model\RestoreContext;
use Restore\Interfaces\ProgressReporter;
use Restore\Interfaces\RestoreStep;
use Restore\Runner\StepRunner;

final class RestoreService
{
    /**
     * Summary of restore
     * @param array<RestoreStep> $steps
     */
    public function __construct(
        private readonly array $steps,
        private readonly StepRunner $runner,
    ) 
    {}
    
    public function restore(RestoreContext $context): void
    {
        $this->runner->run(
            $this->steps,
            $context,
            static fn (RestoreStep $step, RestoreContext $context) => $step->execute($context),
        );
    }
}