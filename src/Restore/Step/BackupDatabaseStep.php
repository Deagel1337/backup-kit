<?php

namespace Restore\Step;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use Restore\Interfaces\BackupStep;

final class BackupDatabaseStep implements BackupStep
{

    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    )
    { }

    public function execute(string $destination): DatabaseDump
    {
        $dump = $this->driver->createDump($destination);
        $this->driver->validateDump($dump);

        return $dump;
    }
}