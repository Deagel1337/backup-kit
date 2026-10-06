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
    /**
     * @param  string|null  $destination  Optionale Zielüberschreibung. stagingDestination im Context hat Vorrang;
     *                                    andernfalls wird dieses Ziel und danach RestoreContext::destination verwendet.
     */
    public function __construct(
        private readonly ArchiveDriver $driver,
        private readonly ?string $destination = null,
    ) {}

    public function name(): string
    {
        return 'Archiv wiederherstellen';
    }

    /**
     * Erstellt ein fehlendes Zielverzeichnis mit Modus 0700 und extrahiert das Archiv dorthin.
     * Ein vom Step angelegtes Verzeichnis wird bei einem Extraktionsfehler entfernt; ein vorhandenes Ziel bleibt bestehen.
     *
     * @throws RuntimeException Wenn Archiv oder Ziel fehlen oder das Ziel nicht erstellt werden kann.
     */
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
