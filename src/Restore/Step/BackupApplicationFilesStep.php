<?php

namespace Restore\Step;

use Restore\Interfaces\BackupStep;
use Restore\Context\BackupContext;

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