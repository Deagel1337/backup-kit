<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver; 

final class RestoreDatabaseStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    )
    {}

    public function name(): string
    {
        return "Datenbank wiederherstellen";
    }

    public function execute(RestoreContext $context): void
    {
        $this->driver->restoreDump($context->dump);
    }
}