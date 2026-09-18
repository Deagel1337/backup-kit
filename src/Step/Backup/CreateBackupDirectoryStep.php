<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;


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