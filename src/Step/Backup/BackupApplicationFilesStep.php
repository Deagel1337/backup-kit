<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Step\Interface\BackupStep;
use Backup\Php\Context\BackupContext;

final class BackupApplicationFilesStep implements BackupStep
{
    public function name(): string
    {
        return 'Anwendungsdateien sichern';
    }

    public function execute(BackupContext $context): void
    {

    }
}