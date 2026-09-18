<?php

namespace Restore\Step;

use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;

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