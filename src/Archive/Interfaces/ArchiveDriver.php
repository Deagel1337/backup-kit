<?php

namespace Deagel1337\Backup\Kit\Archive\Interfaces;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

interface ArchiveDriver
{
    /**
     * Creates an archive.
     *
     * @param  array<string>  $paths
     */
    public function createArchive(array $paths, string $archiveName): ArchiveInfo;

    /**
     * Extracts the content of the archive at the destination.
     */
    public function extractArchive(ArchiveInfo $archive, string $destination): void;

    /**
     * Validates the validity of the archive. It depends on the driver implementation.s
     */
    public function validateArchive(ArchiveInfo $archive): void;

    /**
     * Validates Requirements for a valid archive. Requirements depend on the driver implementation.
     */
    public function validateRequirements(): void;

    /**
     * List the content of the archive
     *
     * @return iterable<ArchiveEntry>
     */
    public function listArchive(ArchiveInfo $archive): iterable;

    /**
     * Returns a iterable list of archives
     *
     * @return iterable<ArchiveInfo>
     */
    public function listArchives(): iterable;
}
