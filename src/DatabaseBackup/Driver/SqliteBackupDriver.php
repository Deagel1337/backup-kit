<?php

namespace Src\DatabaseBackup\Driver\SqliteBackupDriver;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use RuntimeException;
use Src\Traits\CommandTrait;

final class SqliteBackupDriver implements DatabaseBackupDriver 
{
    use CommandTrait;

    public function __construct(
        private readonly DatabaseConnection $connection,
    ) { }

    public function createDump(string|null $backupName = null): DatabaseDump
    {
        $path = $backupName === null
            ? tempnam(sys_get_temp_dir(), 'sqlite_dump_')
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($backupName);

        if ($path === false) {
            throw new RuntimeException('Es konnte keine temporäre Dump-Datei erstellt werden.');
        }

        // .backup erstellt eine konsistente Kopie, auch während die DB in Benutzung ist
        $command = [
            'sqlite3',
            $this->connection->database,
            '.backup ' . escapeshellarg($path),
        ];

        $process = proc_open(
            $command,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (!is_resource($process)) {
            unlink($path);
            throw new RuntimeException('Der Prozess sqlite3 konnte nicht gestartet werden.');
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            unlink($path);
            throw new RuntimeException(
                'Der SQLite-Dump konnte nicht erstellt werden: ' . trim($errorOutput)
            );
        }

        return new DatabaseDump($path, $this->connection->driver, 'sqlite');
    }

    public function validateDump(DatabaseDump $dump): void
    {
        if ($dump->driver !== $this->connection->driver) {
            throw new RuntimeException('Der Dump gehört nicht zum SQLite-Treiber.');
        }

        if (strtolower($dump->format) !== 'sqlite') {
            throw new RuntimeException('Der Dump muss im SQLite-Format vorliegen.');
        }

        if (!$dump->exists()) {
            throw new RuntimeException('Die Dump-Datei existiert nicht.');
        }

        if (!$dump->isReadable()) {
            throw new RuntimeException('Die Dump-Datei ist nicht lesbar.');
        }

        if ($dump->size() === 0) {
            throw new RuntimeException('Die Dump-Datei ist leer.');
        }
    }

    public function restoreDump(DatabaseDump $dump): void
    {
        $this->validateDump($dump);

        // SQLite-Datenbanken sind einzelne Dateien, daher genügt ein Kopiervorgang
        if (!copy($dump->path, $this->connection->database)) {
            throw new RuntimeException(
                'Der SQLite-Dump konnte nicht nach ' . $this->connection->database . ' wiederhergestellt werden.'
            );
        }
    }

    public function validateRequirements(): void
    {
        if (!$this->isCommandAvailable('sqlite3')) {
            throw new RuntimeException('Das Programm sqlite3 ist nicht verfügbar.');
        }
    }
}