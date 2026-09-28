<?php

namespace Deagel1337\Backup\Kit\Step\Restore\Rollback;

use Deagel1337\Backup\Kit\Context\RestoreContext;

interface RestoreRollbackHandler
{
    public function rollback(RestoreContext $context): void;
}