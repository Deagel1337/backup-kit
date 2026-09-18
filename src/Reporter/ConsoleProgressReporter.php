<?php

namespace Backup\Php\Reporter;

use Backup\Php\Reporter\Interface\ProgressReporter;
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
        echo "[{$number}/{$total}] Schritt {$name} Fehlgeschlagen:\n";
        echo $e->getMessage();
    }

    public function finished(): void
    {
        echo "Backup abgeschlossen.\n";
    }
}