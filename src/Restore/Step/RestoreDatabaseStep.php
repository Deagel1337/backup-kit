<?php

namespace Restore\Step;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use Restore\Model\RestoreContext;
use Restore\Interfaces\RestoreStep;

final class RestoreDatabaseStep implements RestoreStep
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    )
    {}

    public function execute(RestoreContext $context): void
    {
        $this->driver->restoreDump($context->dump);
    }
}