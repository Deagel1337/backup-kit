<?php

namespace Deagel1337\Backup\Kit\Application\Backup;

use DateTimeImmutable;
use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Reporter\NullProgressReporter;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Services\BackupService;
use Deagel1337\Backup\Kit\Step\Backup\ArchiveBackupStep;
use Deagel1337\Backup\Kit\Step\Backup\BackupDatabaseStep;
use Deagel1337\Backup\Kit\Step\Backup\CleanupBackupStep;
use Deagel1337\Backup\Kit\Step\Backup\PruneArchivesStep;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use RuntimeException;

/**
 * Vollständiger Backup-Ablauf: Datenbank sichern, zusammen mit Dateien archivieren,
 * den temporären Dump entfernen und alte Archive aufräumen.
 *
 * Die Klasse ist unabhängig von Konsole und Framework. Sie gibt nichts aus, sondern liefert ein
 * {@see BackupResult}. Der Fortschritt kann über einen eigenen {@see ProgressReporter} verfolgt werden,
 * zum Beispiel für Logging, Events oder eine Konsolenausgabe.
 */
final class BackupApplication
{
    public function __construct(
        private readonly DatabaseBackupDriver $database,
        private readonly ArchiveService $archives,
        private readonly ProgressReporter $reporter = new NullProgressReporter,
    ) {}

    /**
     * @param  string  $dumpPath  Zielpfad des temporären Datenbank-Dumps.
     * @param  string  $archiveName  Name des Archivs. Die Namensgebung hängt vom Archivtreiber ab.
     * @param  array<string>  $files  Zusätzliche Dateien oder Verzeichnisse für das Archiv.
     * @param  RetentionPolicy|null  $retention  Wenn gesetzt, werden danach alte Archive entfernt.
     * @param  bool  $removeDump  Entfernt den Dump nach dem Archivieren.
     *
     * @throws RuntimeException Wenn ein Schritt fehlschlägt oder kein Archiv entsteht.
     */
    public function run(
        string $dumpPath,
        string $archiveName,
        array $files = [],
        ?RetentionPolicy $retention = null,
        bool $removeDump = true,
    ): BackupResult {
        $startedAt = new DateTimeImmutable;

        $steps = [
            new BackupDatabaseStep($this->database),
            new ArchiveBackupStep($this->archives, $archiveName),
        ];

        if ($removeDump) {
            $steps[] = new CleanupBackupStep;
        }

        if ($retention !== null) {
            $steps[] = new PruneArchivesStep($this->archives, $retention);
        }

        $context = new BackupContext(destination: $dumpPath, files: $files);

        (new BackupService($steps, new StepRunner($this->reporter)))->backup($context);

        if ($context->archive === null) {
            throw new RuntimeException('Der Backup-Prozess hat kein Archiv erzeugt.');
        }

        return new BackupResult(
            archive: $context->archive,
            dump: $context->dump,
            dumpRemoved: $removeDump,
            pruned: $retention !== null,
            startedAt: $startedAt,
            finishedAt: new DateTimeImmutable,
        );
    }
}
