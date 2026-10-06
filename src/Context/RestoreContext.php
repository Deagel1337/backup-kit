<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;

/**
 * Übergibt Eingaben und Laufzeitstatus zwischen Restore-Schritten.
 *
 * @param  ArchiveInfo|null  $archive  Optionales Dateiarchiv; bei Datenbank-Restores ohne Dateien null.
 * @param  DatabaseDump|null  $dump  Datenbank-Dump, der wiederhergestellt werden soll.
 * @param  string  $destination  Endgültiges Zielverzeichnis der Archivdateien.
 * @param  DatabaseDump|null  $rollbackDump  Lokaler Snapshot für den Rollback; wird vom RestoreService entfernt.
 * @param  string|null  $stagingDestination  Optionales, noch nicht existentes Verzeichnis für eine isolierte Extraktion.
 * @param  bool  $databaseRestoreStarted  Interner Status, der festlegt, ob RestoreService einen Rollback versucht.
 */
final class RestoreContext extends Context
{
    public function __construct(
        public ?ArchiveInfo $archive,
        public ?DatabaseDump $dump,
        public string $destination,
        public ?DatabaseDump $rollbackDump = null,
        public ?string $stagingDestination = null,
        public bool $databaseRestoreStarted = false,
    ) {
        parent::__construct(
            destination: $this->destination,
            dump: $this->dump,
            archive: $this->archive
        );
    }
}
