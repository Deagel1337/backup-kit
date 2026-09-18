<?php

namespace Restore\Step;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;

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