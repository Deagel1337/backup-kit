<?php

namespace Restore\Step;

use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;

final class CleanBackupStep implements BackupStep
{
    public function name(): string
    {
        return 'Temporäre Datein für das Backup löschen';
    }

    public function execute(BackupContext $context): void
    {

    }
}