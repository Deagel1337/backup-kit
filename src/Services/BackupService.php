<?php

namespace Src\Services;

use Restore\Context\BackupContext;
use Restore\Interfaces\ProgressReporter;

final class BackupService 
{
    public function __construct(
        private readonly array $steps,
        private readonly ProgressReporter $progress,
    )
    {}

    public function backup(BackupContext $context): void
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