<?php

namespace Deagel1337\Backup\Kit\Step\Restore\Rollback;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;

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
