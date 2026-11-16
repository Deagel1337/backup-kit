<?php

namespace Deagel1337\Backup\Kit\Reporter;

use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Throwable;

final class CompositeProgressReporter implements ProgressReporter
{
    /**
     * @param  array<ProgressReporter>  $reporters
     */
    public function __construct(
        private readonly array $reporters,
    ) {}

    public function started(int $total): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->started($total);
        }
    }

    public function stepStarted(int $number, int $total, string $name): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->stepStarted($number, $total, $name);
        }
    }

    public function stepFinished(int $number, int $total, string $name): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->stepFinished($number, $total, $name);
        }
    }

    public function stepFailed(int $number, int $total, string $name, Throwable $e): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->stepFailed($number, $total, $name, $e);
        }
    }

    public function finished(): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->finished();
        }
    }
}
