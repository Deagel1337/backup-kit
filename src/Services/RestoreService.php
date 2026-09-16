<?php

namespace Src\Services;

use Restore\Model\RestoreContext;
use Restore\Interfaces\ProgressReporter;
final class RestoreService
{
    public function __construct(
        private readonly array $steps,
        private readonly ProgressReporter $progress,
    ) {}

    public function restore(RestoreContext $context): void
    {
        $total = count($this->steps);

        $this->progress->started($total);


        foreach($this->steps as $index => $step) {
            $number = $index + 1;
            $name = $step->name();

            $this->progress->stepStarted(
                $number,
                $total,
                $name
            );

            $step->execute($context);

            $this->progress->stepFinished(
                $number,
                $total,
                $name
            );
        }

        $this->progress->finished();
    }
}