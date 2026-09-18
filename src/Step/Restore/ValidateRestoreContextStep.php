<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\Archive\Interfaces\ArchiveDriver;


final class ValidateRestoreContextStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $databaseBackupDriver,
        private readonly ArchiveDriver $archiveDriver
    )
    {}
    
    public function name(): string
    {
        return "Validiere den Context für die Wiederherstellung";
    }

    public function execute(RestoreContext $context): void
    {
        $this->databaseBackupDriver->validateRequirements();
        $this->databaseBackupDriver->validateDump($context->dump);
        $this->archiveDriver->validateRequirements();
        $this->archiveDriver->validateArchive($context->archive);
    }
}