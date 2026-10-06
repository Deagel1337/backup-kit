<?php

namespace Deagel1337\Backup\Kit\Reporter;

use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Throwable;

/**
 * Meldet nichts. Standard für Applications, die ohne Konsole laufen, zum Beispiel in Jobs oder Tests.
 */
final class NullProgressReporter implements ProgressReporter
{
    public function started(int $total): void {}

    public function stepStarted(int $number, int $total, string $name): void {}

    public function stepFinished(int $number, int $total, string $name): void {}

    public function stepFailed(int $number, int $total, string $name, Throwable $e): void {}

    public function finished(): void {}
}
