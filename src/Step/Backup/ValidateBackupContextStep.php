<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;

final class ValidateBackupContextStep implements BackupStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $databaseBackupDriver,
    )
    {}
    
    public function name(): string
    {
        return "Validiere den Context für das Erstellen eines Dumps einer Datenbank\n";
    }

    public function execute(BackupContext $context): void
    {
        $this->databaseBackupDriver->validateRequirements();
        $this->databaseBackupDriver->validateDump($context->dump);
    }
}