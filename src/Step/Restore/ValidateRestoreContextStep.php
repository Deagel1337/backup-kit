<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class ValidateRestoreContextStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $databaseBackupDriver,
        private readonly ArchiveDriver $archiveDriver,
        private readonly ?string $destination = null,
    ) {}

    public function name(): string
    {
        return 'Validiere den Context für die Wiederherstellung';
    }

    public function execute(RestoreContext $context): void
    {
        $this->databaseBackupDriver->validateRequirements();
        if ($context->archive !== null) {
            $this->archiveDriver->validateRequirements();
        }

        if ($context->dump) {
            $this->databaseBackupDriver->validateDump($context->dump);
        }
        if ($context->archive) {
            $this->archiveDriver->validateArchive($context->archive);

            if ($this->destination !== null) {
                $context->destination = $this->destination;
            }

            if ($context->destination === '') {
                throw new RuntimeException('Kein Zielverzeichnis für die Wiederherstellung angegeben.');
            }

            $destinationName = basename(rtrim($context->destination, DIRECTORY_SEPARATOR));
            if ($destinationName === '' || $destinationName === '.' || $destinationName === '..') {
                throw new RuntimeException('Ungültiges Zielverzeichnis für die Wiederherstellung.');
            }

            $destinations = [$context->destination];
            if ($context->stagingDestination !== null) {
                $destinations[] = $context->stagingDestination;
            }

            foreach ($destinations as $destination) {
                $parent = dirname($destination);
                if (! is_dir($parent) || ! is_writable($parent)) {
                    throw new RuntimeException('Das Zielverzeichnis für die Wiederherstellung ist nicht beschreibbar.');
                }

                if (is_link(rtrim($destination, DIRECTORY_SEPARATOR)) || (file_exists($destination) && ! is_dir($destination))) {
                    throw new RuntimeException('Das Ziel für die Wiederherstellung ist kein sicheres Verzeichnis.');
                }
            }

            if ($context->stagingDestination !== null) {
                $stagingName = basename(rtrim($context->stagingDestination, DIRECTORY_SEPARATOR));
                if ($stagingName === '' || $stagingName === '.' || $stagingName === '..') {
                    throw new RuntimeException('Ungültiges Staging-Verzeichnis.');
                }
                if (realpath(dirname($context->destination)) !== realpath(dirname($context->stagingDestination))) {
                    throw new RuntimeException('Das Staging-Verzeichnis muss neben dem Restore-Ziel liegen.');
                }
                if ($stagingName === $destinationName) {
                    throw new RuntimeException('Staging-Verzeichnis und Restore-Ziel dürfen nicht identisch sein.');
                }
                if (file_exists($context->stagingDestination) || is_link($context->stagingDestination)) {
                    throw new RuntimeException('Das Staging-Verzeichnis für die Wiederherstellung ist bereits vorhanden.');
                }
            }

            $hasEntries = false;
            foreach ($this->archiveDriver->listArchive($context->archive) as $entry) {
                if (! $entry instanceof ArchiveEntry) {
                    continue;
                }

                $hasEntries = true;
                $path = str_replace('\\', '/', $entry->path);
                if (preg_match('#(^|/)\.\.(/|$)#', $path)) {
                    throw new RuntimeException('Das Archiv enthält einen unsicheren Pfad: '.$entry->path);
                }
            }

            if (! $hasEntries) {
                throw new RuntimeException('Das Archiv enthält keine Einträge.');
            }
        }
    }
}
