<?php

namespace Backup\Php\DatabaseBackup\Interfaces;

use Backup\Php\DatabaseBackup\Model\DatabaseDump;

interface DatabaseBackupDriver 
{
    public function createDump(?string $backupName = null): DatabaseDump;
    public function validateDump(DatabaseDump $dump): void;
    public function restoreDump(DatabaseDump $dump): void;
    public function validateRequirements(): void;
}