<?php

namespace Backup\Php\Services;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Runner\StepRunner;
use Backup\Php\Step\Interface\RestoreStep;

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