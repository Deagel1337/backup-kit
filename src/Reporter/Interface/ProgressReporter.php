<?php

namespace Deagel1337\Backup\Kit\Reporter\Interface;

use Throwable;

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

    public function stepFailed(
        int $number,
        int $total,
        string $name,
        Throwable $e,
    ): void;

    public function finished(): void;
}
