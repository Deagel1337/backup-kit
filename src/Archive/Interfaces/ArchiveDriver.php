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
     *
     * @param  array<string>  $paths  Only extract these archive paths (all if empty).
     * @param  int  $stripComponents  Number of leading path components to remove.
     */
    public function extractArchive(
        ArchiveInfo $archive,
        string $destination,
        array $paths = [],
        int $stripComponents = 0,
    ): void;

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

    /**
     * Behält Archive gemäß den übergebenen Aufbewahrungsregeln und entfernt alle anderen.
     *
     * Die Regeln werden kombiniert: Ein Archiv bleibt erhalten, wenn mindestens eine Regel es auswählt.
     * Mindestens eine Regel muss angegeben werden. Der Wert 0 behält für die jeweilige Regel nichts.
     *
     * @param  int|null  $keepLast  Behält die N neuesten Archive.
     * @param  int|null  $keepDaily  Behält das neueste Archiv der N jüngsten Tage.
     * @param  int|null  $keepWeekly  Behält das neueste Archiv der N jüngsten Wochen.
     * @param  int|null  $keepMonthly  Behält das neueste Archiv der N jüngsten Monate.
     * @param  int|null  $keepYearly  Behält das neueste Archiv der N jüngsten Jahre.
     *
     * @throws \InvalidArgumentException Wenn keine Regel angegeben wurde oder ein Wert negativ ist.
     * @throws \RuntimeException Wenn Archive nicht aufgelistet oder entfernt werden können.
     */
    public function prune(
        ?int $keepLast = null,
        ?int $keepDaily = null,
        ?int $keepWeekly = null,
        ?int $keepMonthly = null,
        ?int $keepYearly = null,
    ): void;
}
