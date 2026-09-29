<?php

namespace Deagel1337\Backup\Kit\Archive\Interfaces;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

interface ArchiveDriver
{
    public function createArchive(array $paths, string $archiveName): ArchiveInfo;
    public function extractArchive(ArchiveInfo $archive, string $destination): void;
    public function validateArchive(ArchiveInfo $archive): void;
    public function validateRequirements(): void;
    public function listArchive(ArchiveInfo $archive): iterable;
    public function listArchives(): iterable;
    }