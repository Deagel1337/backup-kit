<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;
use RuntimeException;

final class CleanupBackupStep implements BackupStep
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