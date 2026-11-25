<?php

namespace Deagel1337\Backup\Kit\Reporter\Laravel\Events;

final readonly class ProgressRunStarted
{
    public function __construct(
        public int $totalSteps,
    ) {}
}
