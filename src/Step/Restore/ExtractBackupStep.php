<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class ExtractBackupStep implements RestoreStep
{
    public function name(): string
    {
        return 'Extrahiert die Backupdateien.';
    }

    public function execute(RestoreContext $context): void
    {

    }
}