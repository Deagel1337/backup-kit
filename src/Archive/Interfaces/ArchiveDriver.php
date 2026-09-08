<?php

namespace Archive\Driver;

use Archive\Model\ArchiveInfo;

interface ArchiveDriver
{
    public function createArchive(array $paths, string $archiveName): ArchiveInfo;

    public function validateArchive(ArchiveInfo $archive): void;

    public function extractArchive(ArchiveInfo $archive, string $destination): void;

    public function validateRequirements(): void;
}