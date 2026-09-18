<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver; 


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