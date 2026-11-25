<?php

namespace Deagel1337\Backup\Kit\Reporter\Laravel;

use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressRunFinished;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressRunStarted;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressStepFailed;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressStepFinished;
use Deagel1337\Backup\Kit\Reporter\Laravel\Events\ProgressStepStarted;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final class EventProgressReporter implements ProgressReporter
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function started(int $total): void
    {
        $this->events->dispatch(new ProgressRunStarted($total));
    }

    public function stepStarted(int $number, int $total, string $name): void
    {
        $this->events->dispatch(new ProgressStepStarted($number, $total, $name));
    }

    public function stepFinished(int $number, int $total, string $name): void
    {
        $this->events->dispatch(new ProgressStepFinished($number, $total, $name));
    }

    public function stepFailed(int $number, int $total, string $name, Throwable $e): void
    {
        $this->events->dispatch(new ProgressStepFailed($number, $total, $name, $e::class));
    }

    public function finished(): void
    {
        $this->events->dispatch(new ProgressRunFinished);
    }
}
