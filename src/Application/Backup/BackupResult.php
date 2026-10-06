<?php

namespace Deagel1337\Backup\Kit\Application\Backup;

use DateTimeImmutable;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;

/**
 * Ergebnis eines Backup-Ablaufs. Enthält keine Ausgabe, damit der Aufrufer
 * (Konsole, Job, Web-Anfrage) die Darstellung selbst bestimmt.
 */
final readonly class BackupResult
{
    public function __construct(
        public ArchiveInfo $archive,
        public ?DatabaseDump $dump,
        public bool $dumpRemoved,
        public bool $pruned,
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $finishedAt,
    ) {}

    /**
     * Dauer des Ablaufs in Sekunden.
     */
    public function duration(): float
    {
        return (float) $this->finishedAt->format('U.u') - (float) $this->startedAt->format('U.u');
    }
}
