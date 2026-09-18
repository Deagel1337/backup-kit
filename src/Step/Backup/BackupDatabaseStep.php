<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;

final class BackupDatabaseStep implements BackupStep
{

    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    )
    { }

    public function name(): string
    {
        return "Datenbank sichern";
    }

    public function execute(BackupContext $context): void
    {
        $dump = $this->driver->createDump($context->destination);
        $this->driver->validateDump($dump);
        $context->dump = $dump; 
    }
}