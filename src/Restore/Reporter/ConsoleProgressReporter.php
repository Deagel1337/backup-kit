<?php

namespace Restore\Reporter;

use Restore\Interfaces\ProgressReporter;
use Throwable;

final class ConsoleProgressReporter implements ProgressReporter
{
    public function started(int $total): void
    {
        echo "Backup gestartet({$total} Schritte)\n";
    }

    public function stepStarted(int $number, int $total, string $name): void
    {
        echo "[{$number}/{$total}] {$name} ...";   
    }

    public function stepFinished(int $number, int $total, string $name): void
    {
        echo "OK\n";
    }

    public function stepFailed(int $number, int $total, string $name, Throwable $e): void
    {
        echo "[{$number}/{$total}] Schritt {$name} Fehlgeschlagen:\n" . $e;
    }

    public function finished(): void
    {
        echo "Backup abgeschlossen.\n";
    }
}