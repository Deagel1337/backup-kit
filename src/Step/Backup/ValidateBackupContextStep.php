<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use RuntimeException;

final class ValidateBackupContextStep implements BackupStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $databaseBackupDriver,
    ) {}

    public function name(): string
    {
        return "Validiere den Context für das Erstellen eines Dumps einer Datenbank\n";
    }

    /**
     * Validates the context of on archive for backup steps
     */
    public function execute(BackupContext $context): void
    {
        $this->databaseBackupDriver->validateRequirements();

        $destinationDirectory = dirname($context->destination);
        if ($context->destination === '' || ! is_dir($destinationDirectory) || ! is_writable($destinationDirectory)) {
            throw new RuntimeException('Das Zielverzeichnis für den Datenbank-Dump ist nicht beschreibbar.');
        }

        foreach ($context->getFiles() as $path) {
            if (! file_exists($path) || ! is_readable($path)) {
                throw new RuntimeException('Die Backup-Quelle ist nicht vorhanden oder nicht lesbar: '.$path);
            }
        }

        if ($context->dump && $context->dump->exists()) {
            $this->databaseBackupDriver->validateDump($context->dump);
        }
    }
}
