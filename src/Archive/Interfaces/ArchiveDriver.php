<?php

namespace Backup\Php\Archive\Interfaces;

use Backup\Php\Archive\Model\ArchiveInfo;

interface ArchiveDriver
{
    public function createArchive(array $paths, string $archiveName): ArchiveInfo;

    public function validateArchive(ArchiveInfo $archive): void;

    public function extractArchive(ArchiveInfo $archive, string $destination): void;

    public function listContent(ArchiveInfo $archive): string;

    public function validateRequirements(): void;
}