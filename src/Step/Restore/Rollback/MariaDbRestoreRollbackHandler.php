<?php

namespace Backup\Php\Step\Restore\Rollback;

use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\Step\Restore\Rollback\RestoreRollbackHandler;

final class MariaDbRestoreRollbackHandler implements RestoreRollbackHandler
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    ) {}

    public function rollback(RestoreContext $context): void 
    {
        if ($context->rollbackDump === null) {
            return;
        }

        $this->driver->restoreDump(
            $context->rollbackDump
        );
    }
}