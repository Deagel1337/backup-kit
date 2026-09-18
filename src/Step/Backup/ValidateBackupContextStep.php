<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\Step\Interface\BackupStep;

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