<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;


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
        $this->archiveDriver->validateRequirements();
        
        if($context->dump) {
            $this->databaseBackupDriver->validateDump($context->dump);
        }
        if($context->archive) {
            $this->archiveDriver->validateArchive($context->archive);
        }
    }
}