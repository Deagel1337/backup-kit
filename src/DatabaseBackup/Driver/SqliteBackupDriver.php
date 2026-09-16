<?php

namespace DatabaseBackup\Driver\SqliteBackupDriver;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use DatabaseBackup\Exception\EmptyDumpException;
use DatabaseBackup\Exception\InvalidDumpFormatException;
use DatabaseBackup\Exception\InvalidDumpDriverException;
use DatabaseBackup\Exception\DumpNotReadableException;
use DatabaseBackup\Exception\DumpNotFoundException;
use Process\ProcessRunner\ProcessRunner;
use Process\Runner\ProcOpenProcessRunner;
use RuntimeException;
use Src\Traits\CommandTrait;

final class SqliteBackupDriver implements DatabaseBackupDriver 
{
    use CommandTrait;

    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly ProcessRunner $process = new ProcOpenProcessRunner(),
    ) { }

    private function processRunner(): ProcessRunner
    {
        return $this->process;
    }

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

        $result = $this->process->run($command);

        if($result->exitCode !== 0) {
            unlink($path);
            throw new RuntimeException('Der Prozess sqlite3 konnte nicht gesichert werden.');
        }

        return new DatabaseDump($path, $this->connection->driver, 'sqlite');
    }

    public function validateDump(DatabaseDump $dump): void
    {
        if ($dump->driver !== $this->connection->driver) {
            throw new InvalidDumpDriverException("Der Dump gehört nicht zum SQLite-Treiber.");
        }

        if (strtolower($dump->format) !== 'sqlite') {
            throw new InvalidDumpFormatException();
        }

        if (!$dump->exists()) {
            throw new DumpNotFoundException();
        }

        if (!$dump->isReadable()) {
            throw new DumpNotReadableException();
        }

        if ($dump->size() === 0) {
            throw new EmptyDumpException();
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