<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\Step\Interface\RestoreStep;
use RuntimeException;

final class CreateDatabaseBackupStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
        private readonly string $backupName = "",
    ) {}

    public function name(): string
    {
        return 'Erstellt ein sicherheits Dump der Datenbank, bevor der Restore-Prozess losgeht.';
    }

    public function execute(RestoreContext $context): void
    {
        $dump = $this->driver->createDump($this->backupName);
        
        if(!$dump->exists()) {
            throw new RuntimeException('Konnte kein Datenbank Dump erstellen.');
        }

        $context->dump = $dump;
    }
}