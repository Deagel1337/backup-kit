<?php

namespace Backup\Php\Step\Restore\Rollback;

use Backup\Php\Context\RestoreContext;

interface RestoreRollbackHandler
{
    public function rollback(RestoreContext $context): void;
}