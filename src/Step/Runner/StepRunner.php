<?php

namespace Deagel1337\Backup\Kit\Step\Runner;

use Deagel1337\Backup\Kit\Context\Context;
use Deagel1337\Backup\Kit\Step\Interface\ProgressStep;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use TContext;
use Throwable;

final class StepRunner
{
    public function __construct(
        private readonly ProgressReporter $progress,
    ) {}

    /**
     * @template TStep of ProgressStep
     * @template TContext of Context
     * @param array<TStep> $steps
     * @param TContext $context
     * @param callable(TStep, TContext): void $execute
     * @return void
     */
    public function run(
        array $steps,
        Context $context,
        callable $execute
    ): void
    {
        $total = count($steps);

        $this->progress->started($total);

        foreach ($steps as $index => $step) {
            $number = $index + 1;
            $name = $step->name();

            $this->progress->stepStarted(
                $number,
                $total,
                $name,
            );

            try {
                $execute($step, $context);

                $this->progress->stepFinished(
                    $number,
                    $total,
                    $name
                );
            } catch (Throwable $e) {
                $this->progress->stepFailed(
                    $number,
                    $total,
                    $name,
                    $e
                );

                throw $e;
            }
        }

        $this->progress->finished();
    }
}