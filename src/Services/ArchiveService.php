<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

/**
 * Ein Orchestrator für den Driver. Die Klasse ist so gedacht klein zu sein.
 * Der Service selber sollte keine Informationen über die Implementierung der Driver tragen.
 */
final class ArchiveService
{
    /**
     * Injetziert einen Driver, der für die Archiverung verantwortlich ist und keine genauen Implementationsdetails hat
     */
    public function __construct(
        private readonly ArchiveDriver $driver
    ) {
        $this->driver->validateRequirements();
    }

    /**
     * Erstellt ein Archiv eines Types
     *
     * @param  array<string>  $paths  Die Pfade zu den Dateien/Ordner
     * @return ArchiveInfo Gibt die Informationen zum erstellten Archiv zurück
     */
    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        return $this->driver->createArchive($paths, $archiveName);
    }

    /**
     * Extrahiert den Inhalt eines Archives
     *
     * @param  ArchiveInfo  $archive  Die Archivinformationen vom Ziel
     * @param  string  $destination  Der Ablageort für die Extrahierung
     */
    public function extractArchive(ArchiveInfo $archive, string $destination): void
    {
        $this->driver->validateArchive($archive);

        $this->driver->extractArchive($archive, $destination);
    }

    /**
     * Gibt den Inhalt eines Archives zurück
     *
     * @return iterable<ArchiveEntry>
     */
    public function listArchiveContent(ArchiveInfo $archive): iterable
    {
        $this->driver->validateArchive($archive);

        return $this->driver->listArchive($archive);
    }

    /**
     * list all Alrchives in the archive directory. It dependes on the driver implementation
     *
     * @return iterable<ArchiveInfo>
     */
    public function listArchives(): iterable
    {
        return $this->driver->listArchives();
    }

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
    ): void {
        $this->driver->prune($keepLast, $keepDaily, $keepWeekly, $keepMonthly, $keepYearly);
    }
}
