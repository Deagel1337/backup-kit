<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class CheckBackupExistsStep implements RestoreStep
{
    public function name(): string
    {
        return 'Überprüf, ob das Backup vorhanden ist.';
    }

    public function execute(RestoreContext $context): void
    {

    }
}