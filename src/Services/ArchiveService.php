<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;

final class ArchiveService
{
    public function __construct(
        private readonly ArchiveDriver $driver
    ) {
        $this->driver->validateRequirements();
    }

    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        return $this->driver->createArchive($paths, $archiveName);
    }

    public function extractArchive(ArchiveInfo $archive, string $destination): void
    {
        $this->driver->validateArchive($archive);

        $this->driver->extractArchive($archive, $destination);
    }

    public function listArchiveContent(ArchiveInfo $archive): string 
    {
        $this->driver->validateArchive($archive);

        return $this->driver->listContent($archive);
    }
}