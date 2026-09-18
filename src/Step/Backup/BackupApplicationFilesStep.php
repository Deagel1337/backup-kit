<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Step\Interface\BackupStep;
use Backup\Php\Context\BackupContext;
use RuntimeException;

final class BackupApplicationFilesStep implements BackupStep
{
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