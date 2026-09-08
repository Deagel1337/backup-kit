<?php

namespace Archive\Service;

use Archive\Driver\ArchiveDriver;
use Archive\Model\ArchiveInfo;

final class ArchiveService
{
    public function __construct(
        private readonly ArchiveDriver $driver
    ) {}

    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        return $this->driver->createArchive($paths, $archiveName);
    }

    public function extractArchive(ArchiveInfo $archive, string $destination): void
    {
        $this->driver->extractArchive($archive, $destination);
    }
}