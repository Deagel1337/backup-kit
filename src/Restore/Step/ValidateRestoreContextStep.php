<?php

namespace Restore\Step;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;
use Archive\Driver\ArchiveDriver;

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