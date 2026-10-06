<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Closure;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class RestoreDatabaseStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
        private readonly ?Closure $healthCheck = null,
    ) {}

    public function name(): string
    {
        return 'Datenbank wiederherstellen';
    }

    public function execute(RestoreContext $context): void
    {
        if (! $context->dump) {
            throw new RuntimeException('No dump given');
        }

        $context->databaseRestoreStarted = true;
        $this->driver->restoreDump($context->dump);
        ($this->healthCheck)?->__invoke($context);
    }
}
