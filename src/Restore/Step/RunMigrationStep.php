<?php

namespace Restore\Step;

use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;

final class RunMigrationStep implements RestoreStep
{
    public function name(): string
    {
        return 'Lauf die Migrationen durch.';
    }

    public function execute(RestoreContext $context): void
    {

    }
}