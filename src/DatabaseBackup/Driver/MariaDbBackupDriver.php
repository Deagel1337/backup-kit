<?php

namespace DatabaseBackup\Driver\MariaDbDriver;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Exception\EmptyDumpException;
use DatabaseBackup\Exception\InvalidDumpFormatException;
use DatabaseBackup\Exception\InvalidDumpDriverException;
use DatabaseBackup\Exception\DumpNotReadableException;
use DatabaseBackup\Exception\DumpNotFoundException;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use Process\ProcessRunner\ProcessRunner;
use Process\Runner\ProcOpenProcessRunner;
use Src\Traits\CommandTrait;
use RuntimeException;

final class MariaDbBackupDriver implements DatabaseBackupDriver
{
    use CommandTrait;
    
    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly ProcessRunner $process = new ProcOpenProcessRunner()    
    ) { }

    private function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    public function createDump(?string $backupName = null): DatabaseDump
    {
        $path = $backupName === null
            ? tempnam(sys_get_temp_dir(), 'mariadb_dump_')
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($backupName);

        if ($path === false) {
            throw new RuntimeException(
                'Es konnte keine temporäre Dump-Datei erstellt werden.'
            );
        }

        $command = [
            'mariadb-dump',
            '--host=' . $this->connection->host,
            '--port=' . $this->connection->port,
            '--user=' . $this->connection->username,
            $this->connection->database,
        ];

        $result = $this->process->run(
            $command,
            ['MYSQL_PWD' => $this->connection->password],
            null,
            $path
        );

        if ($result->exitCode !== 0) {
            @unlink($path);

            throw new RuntimeException(
                'Das MariaDB-Dump konnte nicht erstellt werden: '
                . trim($result->errorOutput)
            );
        }

        return new DatabaseDump(
            $path,
            $this->connection->driver,
            'sql'
        );
    }

    public function validateDump(DatabaseDump $dump): void
    {
        if ($dump->driver !== $this->connection->driver) {
            throw new InvalidDumpDriverException("Der Dump gehört nicht zum MariaDB-Treiber.");
        }

        if (strtolower($dump->format) !== 'sql') {
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

        $command = [
            'mariadb',
            '--host=' . $this->connection->host,
            '--port=' . $this->connection->port,
            '--user=' . $this->connection->username,
            $this->connection->database,
        ];

        $result = $this->process->run($command, ['MYSQL_PWD' => $this->connection->password], null, null, $dump->path);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das MariaDB-Dump konnte nicht wiederhergestellt werden: ' . trim($result->errorOutput)
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