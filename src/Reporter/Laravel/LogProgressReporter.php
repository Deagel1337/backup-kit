<?php

namespace Deagel1337\Backup\Kit\Reporter\Laravel;

use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Psr\Log\LoggerInterface;
use Throwable;

final class LogProgressReporter implements ProgressReporter
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function started(int $total): void
    {
        $this->logger->info('Backup-kit progress started.', [
            'total_steps' => $total,
        ]);
    }

    public function stepStarted(int $number, int $total, string $name): void
    {
        $this->logger->info('Backup-kit step started.', [
            'step' => $number,
            'total_steps' => $total,
            'name' => $name,
        ]);
    }

    public function stepFinished(int $number, int $total, string $name): void
    {
        $this->logger->info('Backup-kit step finished.', [
            'step' => $number,
            'total_steps' => $total,
            'name' => $name,
        ]);
    }

    public function stepFailed(int $number, int $total, string $name, Throwable $e): void
    {
        $this->logger->error('Backup-kit step failed.', [
            'step' => $number,
            'total_steps' => $total,
            'name' => $name,
            'exception_class' => $e::class,
        ]);
    }

    public function finished(): void
    {
        $this->logger->info('Backup-kit progress finished.');
    }
}
