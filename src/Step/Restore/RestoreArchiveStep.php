<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class RestoreArchiveStep implements RestoreStep
{
    public function __construct(
        private readonly ArchiveDriver $driver,
        private readonly ?string $destination = null,
    ) {}

    public function name(): string
    {
        return 'Archiv wiederherstellen';
    }

    public function execute(RestoreContext $context): void
    {
        if (! $context->archive) {
            throw new RuntimeException('No Archive available');
        }

        $destination = $context->stagingDestination ?? $this->destination ?? $context->destination;
        if ($destination === '') {
            throw new RuntimeException('Kein Zielverzeichnis für die Wiederherstellung angegeben.');
        }

        if ($context->stagingDestination !== null && (file_exists($destination) || is_link($destination))) {
            throw new RuntimeException('Das Staging-Verzeichnis für die Wiederherstellung ist bereits vorhanden.');
        }

        $createdDestination = ! is_dir($destination);
        if (is_link($destination) || ($createdDestination && ! mkdir($destination, 0700, true))) {
            throw new RuntimeException('Das Zielverzeichnis für die Wiederherstellung konnte nicht erstellt werden.');
        }

        try {
            $this->driver->extractArchive($context->archive, $destination);
        } catch (\Throwable $exception) {
            if ($createdDestination) {
                $this->removeDirectory($destination);
            }

            throw $exception;
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory) || is_link($directory)) {
            @unlink($directory);

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            if ($entry->isDir() && ! $entry->isLink()) {
                @rmdir($entry->getPathname());
            } else {
                @unlink($entry->getPathname());
            }
        }

        @rmdir($directory);
    }
}
