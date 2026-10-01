<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use Deagel1337\Backup\Kit\Context\BackupContext;
use RuntimeException;

final class BackupApplicationFilesStep implements BackupStep
{
    /**
     * 
     * @param ArchiveDriver $archive
     * @param string $name
     * @param array<string> $paths
     */
    public function __construct(
        private readonly ArchiveDriver $archive,
        private string $name,
        private array $paths,
    ) { }

    public function name(): string
    {
        return 'Anwendungsdateien sichern';
    }

    public function execute(BackupContext $context): void
    {
        $context->archive = $this->archive->createArchive($this->paths, $this->name);

        if(!$context->archive->exists()) {
            throw new RuntimeException('Das Erstellen eines Backups-Archives ist fehlgeschlagen');
        }
    }
}