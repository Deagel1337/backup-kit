<?php

namespace Deagel1337\Backup\Kit\Reporter;

use Deagel1337\Backup\Kit\Reporter\Interface\ProcessReporter;

final class ConsoleProcessReporter implements ProcessReporter
{
    public function command(array $command): void
    {
        echo PHP_EOL;
        echo "Would execute:" . PHP_EOL;
        echo ' ' . $this->formatCommand($command) . PHP_EOL;
    }

    private function formatCommand(array $command): string
    {
        return implode(' ', array_map(
            static fn (string $argument): string => escapeshellarg($argument),
            $command
        ));
    }
}