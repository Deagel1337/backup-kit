<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;
use Backup\Php\Traits\HumanReadableTrait;

final class CheckDiskSpaceStep implements BackupStep
{
    use HumanReadableTrait;

    public function name(): string
    {
        return 'Backup-Verzeichnis erstellen';
    }

    public function execute(BackupContext $context): void
    {
        $freeSpace = diskfreespace('/');

        if($freeSpace !== false) {
            echo "Free space: " . $this->formatBytes($freeSpace) . " bytes";
        } else {
            echo "Could not determine free space";
        }
    }
}