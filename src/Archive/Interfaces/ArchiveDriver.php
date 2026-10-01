<?php

namespace Deagel1337\Backup\Kit\Archive\Interfaces;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;

interface ArchiveDriver
{
    /**
     * Creates an archive.
     * @param array<string> $paths
     * @param string $archiveName
     * @return ArchiveInfo
     */
    public function createArchive(array $paths, string $archiveName): ArchiveInfo;
    /**
     * Extracts the content of the archive at the destination.
     * @param ArchiveInfo $archive
     * @param string $destination
     * @return void
     */
    public function extractArchive(ArchiveInfo $archive, string $destination): void;
    /**
     * Validates the validity of the archive. It depends on the driver implementation.s
     * @param ArchiveInfo $archive
     * @return void
     */
    public function validateArchive(ArchiveInfo $archive): void;

    /**
     * Validates Requirements for a valid archive. Requirements depend on the driver implementation.
     * @return void
     */
    public function validateRequirements(): void;
    /**
     * List the content of the archive
     * @param ArchiveInfo $archive
     * @return iterable<ArchiveEntry>
     */
    public function listArchive(ArchiveInfo $archive): iterable;
    /**
     * Returns a iterable list of archives
     * @return iterable<ArchiveInfo>
     */
    public function listArchives(): iterable;
    }