<?php

namespace Restore\Step;

use Restore\Interfaces\BackupStep;
use Restore\Context\BackupContext;

final class CreateBackupDirectoryStep implements BackupStep
{
    public function name(): string
    {
        return 'Backup-Verzeichnis erstellen';
    }

    public function execute(BackupContext $context): void
    {

    }
}