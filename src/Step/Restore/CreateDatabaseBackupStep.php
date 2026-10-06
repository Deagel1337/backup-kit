<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class CreateDatabaseBackupStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
        private readonly string $backupName = '',
    ) {}

    public function name(): string
    {
        return 'Erstellt ein sicherheits Dump der Datenbank, bevor der Restore-Prozess losgeht.';
    }

    public function execute(RestoreContext $context): void
    {
        $dump = $this->driver->createDump($this->backupName);

        if (! $dump->exists()) {
            throw new RuntimeException('Konnte kein Datenbank Dump erstellen.');
        }

        $this->driver->validateDump($dump);
        $context->rollbackDump = $dump;
    }
}
