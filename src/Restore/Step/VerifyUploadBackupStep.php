<?php

namespace Restore\Step;

use Restore\Context\BackupContext;
use Restore\Interfaces\BackupStep;

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