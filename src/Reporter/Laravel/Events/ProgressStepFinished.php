<?php

namespace Deagel1337\Backup\Kit\Reporter\Laravel\Events;

final readonly class ProgressStepFinished
{
    public function __construct(
        public int $step,
        public int $totalSteps,
        public string $name,
    ) {}
}
