<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use Deagel1337\Backup\Kit\Traits\HumanReadableTrait;
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