<?php

namespace Backup\Php\DatabaseBackup\Driver;

use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\Exception\DumpDriverException\DumpNotFoundException;
use Backup\Php\Exception\DumpDriverException\DumpNotReadableException;
use Backup\Php\Exception\DumpDriverException\EmptyDumpException;
use Backup\Php\Exception\DumpDriverException\InvalidDumpDriverException;
use Backup\Php\Exception\DumpDriverException\InvalidDumpFormatException;
use Backup\Php\Process\Interface\ProcessRunner;
use Backup\Php\Process\Runner\ProcOpenProcessRunner;
use Backup\Php\Traits\CommandTrait;
use RuntimeException;


final class PostgresBackupDriver implements DatabaseBackupDriver 
{
    use CommandTrait;
    
    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly ProcessRunner $process = new ProcOpenProcessRunner(),
    ) { }

    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    public function createDump(string|null $backupName = null): DatabaseDump
    {
        $path = $backupName === null
            ? tempnam(sys_get_temp_dir(), 'postgres_dump_')
            : sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($backupName);

        if ($path === false) {
            throw new RuntimeException('Es konnte keine temporäre Dump-Datei erstellt werden.');
        }

        $command = [
            'pg_dump',
            '--host=' . $this->connection->host,
            '--port=' . $this->connection->port,
            '--username=' . $this->connection->username,
            '--format=plain',
            $this->connection->database,
        ];

        $result = $this->process->run($command, ['PGPASSWORD' => $this->connection->password], null, $path);

        if ($result->exitCode !== 0) {
            unlink($path);
            throw new RuntimeException(
                'Das Postgres-Dump konnte nicht erstellt werden: ' . trim($result->errorOutput)
            );
        }

        return new DatabaseDump($path, $this->connection->driver, 'sql');
    }

    public function validateDump(DatabaseDump $dump): void
    {
        if ($dump->driver !== $this->connection->driver) {
            throw new InvalidDumpDriverException("Der Dump gehört nicht zum Postgres-Treiber.");
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
            'psql',
            '--host=' . $this->connection->host,
            '--port=' . $this->connection->port,
            '--username=' . $this->connection->username,
            $this->connection->database,
        ];

        
        $result = $this->process->run($command, ['PGPASSWORD' => $this->connection->password], null, null, $dump->path);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Postgres-Dump konnte nicht wiederhergestellt werden: ' . trim($result->errorOutput)
            );
        }
    }

    public function validateRequirements(): void
    {
        $missingCommands = [];

        foreach (['psql', 'pg_dump'] as $command) {
            if (!$this->isCommandAvailable($command)) {
                $missingCommands[] = $command;
            }
        }

        if ($missingCommands !== []) {
            throw new RuntimeException(
                'Folgende Postgres-Programme sind nicht verfügbar: '
                . implode(', ', $missingCommands)
            );
        }
    }
}