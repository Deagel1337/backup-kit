<?php

namespace Restore\Step;

use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;
use DatabaseBackup\Driver\DatabaseBackupDriver;

final class ValidateBackupContextStep implements BackupStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $databaseBackupDriver,
    )
    {}
    
    public function name(): string
    {
        return "Validiere den Context für das Erstellen eines Dumps einer Datenbank";
    }

    public function execute(BackupContext $context): void
    {
        $this->databaseBackupDriver->validateRequirements();
        $this->databaseBackupDriver->validateDump($context->dump);
    }
}