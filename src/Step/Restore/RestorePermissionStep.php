<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class RestorePermissionStep implements RestoreStep
{
    public function name(): string
    {
        return 'Setzt die richten Datei-Rechte';
    }

    public function execute(RestoreContext $context): void
    {
        
    }
}