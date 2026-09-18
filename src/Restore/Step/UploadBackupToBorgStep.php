<?php

namespace Restore\Step;

use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;

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