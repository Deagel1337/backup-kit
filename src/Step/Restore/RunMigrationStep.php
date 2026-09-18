<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

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