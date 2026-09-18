<?php

namespace Restore\Step;

use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;

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