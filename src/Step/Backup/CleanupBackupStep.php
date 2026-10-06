<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use RuntimeException;

final class CleanupBackupStep implements BackupStep
{
    public function name(): string
    {
        return 'Temporäre Datein für das Backup löschen';
    }

    public function execute(BackupContext $context): void
    {
        if ($context->dump) {
            if (! unlink($context->dump->path)) {
                throw new RuntimeException('Das Backup konnte nicht gelöscht werden.');
            }
        }
    }
}
