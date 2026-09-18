<?php

namespace Restore\Step;

use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;
use RuntimeException;

final class CleanBackupStep implements BackupStep
{
    public function name(): string
    {
        return 'Temporäre Datein für das Backup löschen';
    }

    public function execute(BackupContext $context): void
    {
        if(!unlink($context->dump->path)) {
            throw new RuntimeException('Das Backup konnte nicht gelöscht werden.');
        }
    }
}