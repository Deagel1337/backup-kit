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
}
