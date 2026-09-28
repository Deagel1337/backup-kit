<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;

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