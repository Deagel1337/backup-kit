<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use RuntimeException;

/**
 * Archiviert den Datenbank-Dump aus dem Kontext zusammen mit den zusätzlichen Dateien des Kontexts.
 */
final class ArchiveBackupStep implements BackupStep
{
    public function __construct(
        private readonly ArchiveService $archives,
        private readonly string $archiveName,
    ) {}

    public function name(): string
    {
        return 'Backup archivieren';
    }

    /**
     * @throws RuntimeException Wenn weder ein Dump noch Dateien zum Archivieren vorhanden sind.
     */
    public function execute(BackupContext $context): void
    {
        $paths = $context->getFiles();

        if ($context->dump !== null) {
            array_unshift($paths, $context->dump->path);
        }

        if ($paths === []) {
            throw new RuntimeException('Es gibt nichts zu archivieren.');
        }

        $context->archive = $this->archives->createArchive($paths, $this->archiveName);
    }
}
