<?php

namespace Restore\Interfaces;

interface ProgressReporter
{
    public function started(int $total): void;
    public function stepStarted(
        int $number,
        int $total,
        string $name
    ): void;

    public function stepFinished(
        int $number,
        int $total,
        string $name
    ): void;

    public function finished(): void;
}