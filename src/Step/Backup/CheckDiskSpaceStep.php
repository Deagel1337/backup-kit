<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;
use Backup\Php\Traits\HumanReadableTrait;
use RuntimeException;

final class CheckDiskSpaceStep implements BackupStep
{
    use HumanReadableTrait;

    public function __construct(
        private readonly string $path = "/",
    ){ }

    public function name(): string
    {
        return 'Backup-Verzeichnis erstellen';
    }

    public function execute(BackupContext $context): void
    {
        $freeSpace = diskfreespace($this->path);

        if($freeSpace !== false) {
            echo "Free space: " . $this->formatBytes($freeSpace) . " bytes";
        } else {
            echo "Could not determine free space";
            throw new RuntimeException('Nicht genug Speicherplatz verfügbar.');
        }
    }
}