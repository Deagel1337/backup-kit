<?php

namespace Restore\Step;

use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;

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