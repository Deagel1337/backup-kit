<?php

namespace DatabaseBackup\Service\DatabaseBackupService;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use RuntimeException;

final class DatabaseBackupService
{
    private static ?self $instance = null;

    private bool $backupInProgress = false;

    private function __construct(
        private readonly DatabaseBackupDriver $driver
    ) { }

    public static function getInstance(DatabaseBackupDriver $driver): self
    {
        if (self::$instance === null) {
            self::$instance = new self($driver);
        }

        return self::$instance;
    }

    private function __clone() { }

    public function __wakeup(): void
    {
        throw new RuntimeException('Singleton darf nicht deserialisiert werden.');
    }

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