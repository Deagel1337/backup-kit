<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use RuntimeException;

final class CreateBackupDirectoryStep implements BackupStep
{
    public function name(): string
    {
        return 'Backup-Verzeichnis erstellen';
    }

    public function execute(BackupContext $context): void
    {
        if (! mkdir($context->destination, 0777, true)) {
            throw new RuntimeException(('Konnte das Backup-Verzeichnis nicht erstellen'));
        }
    }
}
