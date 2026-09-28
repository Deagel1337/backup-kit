<?php

namespace Deagel1337\Backup\Kit\DatabaseBackup\Interfaces;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;

interface DatabaseBackupDriver 
{
    public function createDump(?string $backupName = null): DatabaseDump;
    public function validateDump(DatabaseDump $dump): void;
    public function restoreDump(DatabaseDump $dump): void;
    public function validateRequirements(): void;
}