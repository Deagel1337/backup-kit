<?php

namespace DatabaseBackup\Driver\MariaDbDriver;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use RuntimeException;
use Src\Traits\CommandTrait;

final class MariaDbBackupDriver implements DatabaseBackupDriver
{
    use CommandTrait;
    
    public function __construct(
        private readonly DatabaseConnection $connection,
    ) { }

    public function createDump(?string $backupName = null): DatabaseDump
    {
        $path = $backupName === null
            ? tempnam(sys_get_temp_dir(), 'mariadb_dump_')
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($backupName);

        if ($path === false) {
            throw new RuntimeException('Es konnte keine temporäre Dump-Datei erstellt werden.');
        }

        $command = [
            'mariadb-dump',
            '--host=' . $this->connection->host,
            '--port=' . $this->connection->port,
            '--user=' . $this->connection->username,
            $this->connection->database,
        ];

        $process = proc_open(
            $command,
            [
                1 => ['file', $path, 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            ['MYSQL_PWD' => $this->connection->password],
        );

        if (!is_resource($process)) {
            unlink($path);
            throw new RuntimeException('Der Prozess mariadb-dump konnte nicht gestartet werden.');
        }

        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            unlink($path);
            throw new RuntimeException(
                'Der MariaDB-Dump konnte nicht erstellt werden: ' . trim($errorOutput)
            );
        }

        return new DatabaseDump($path, $this->connection->driver, 'sql');
    }

    public function validateDump(DatabaseDump $dump): void
    {
        if ($dump->driver !== $this->connection->driver) {
            throw new RuntimeException('Der Dump gehört nicht zum MariaDB-Treiber.');
        }

        if (strtolower($dump->format) !== 'sql') {
            throw new RuntimeException('Der Dump muss im SQL-Format vorliegen.');
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

        $command = [
            'mariadb',
            '--host=' . $this->connection->host,
            '--port=' . $this->connection->port,
            '--user=' . $this->connection->username,
            $this->connection->database,
        ];

        $process = proc_open(
            $command,
            [
                0 => ['file', $dump->path, 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            ['MYSQL_PWD' => $this->connection->password],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Der Prozess mariadb konnte nicht gestartet werden.');
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Der MariaDB-Dump konnte nicht wiederhergestellt werden: ' . trim($errorOutput)
            );
        }
    }

    public function validateRequirements(): void
    {
        $missingCommands = [];

        foreach (['mariadb', 'mariadb-dump'] as $command) {
            if (!$this->isCommandAvailable($command)) {
                $missingCommands[] = $command;
            }
        }

        if ($missingCommands !== []) {
            throw new RuntimeException(
                'Folgende MariaDB-Programme sind nicht verfügbar: '
                . implode(', ', $missingCommands)
            );
        }
    }
}