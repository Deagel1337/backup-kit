<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;

final class UploadBackupToBorgStep implements BackupStep
{
    public function name(): string
    {
        return 'Backup hochladen ins Borg Storage hochladen.';
    }

    public function execute(BackupContext $context): void
    {

    }
}