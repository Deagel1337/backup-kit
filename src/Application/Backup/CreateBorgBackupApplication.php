<?php

namespace Backup\Php\Application\Backup;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;

final class CreateBorgBackupApplication
{
    public function __construct(
        private readonly ArchiveDriver $archive,
    ) {}

    public function run(array $paths, string $name): ArchiveInfo
    {
        return $this->archive->createArchive($paths, $name);
    }

    public function listBorgArchiveContent(ArchiveInfo $archive): void
    {
        $this->archive->listContent($archive);
    }
}
