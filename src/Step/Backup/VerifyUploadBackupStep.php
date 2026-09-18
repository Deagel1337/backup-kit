<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;

final class VerifyUploadBackupStep implements BackupStep
{
    public function name(): string
    {
        return 'Verifizieren, ob das Backup erfolgreich hochgeladen worden ist.';
    }

    public function execute(BackupContext $context): void
    {

    }
}