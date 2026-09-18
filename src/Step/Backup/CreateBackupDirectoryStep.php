<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;
use RuntimeException;


final class CreateBackupDirectoryStep implements BackupStep
{
    public function name(): string
    {
        return 'Backup-Verzeichnis erstellen';
    }

    public function execute(BackupContext $context): void
    {
        if(!mkdir($context->destination, 0777, true)) {
            throw new RuntimeException(("Konnte das Backup-Verzeichnis nicht erstellen"));
        }
    }
}