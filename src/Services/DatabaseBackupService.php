<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use RuntimeException;

final class DatabaseBackupService
{
    private bool $backupInProgress = false;

    private function __construct(
        private readonly DatabaseBackupDriver $driver
    ) { }

    public function createDump(?string $backupName = null): DatabaseDump
    {
        if ($this->backupInProgress) {
            throw new RuntimeException('Es läuft bereits ein Backup, bitte warten.');
        }

        $this->backupInProgress = true;

        try {
            return $this->driver->createDump($backupName);
        } finally {
            $this->backupInProgress = false;
        }
    }

    public function validateDump(DatabaseDump $dump): void
    {
        $this->driver->validateDump($dump);
    }

    public function restoreDump(DatabaseDump $dump): void
    {
        $this->driver->restoreDump($dump);
    }

    public function validateRequirements(): void
    {
        $this->driver->validateRequirements();
    }
}