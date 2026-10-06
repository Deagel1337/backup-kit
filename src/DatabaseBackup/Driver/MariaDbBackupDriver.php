<?php

namespace Deagel1337\Backup\Kit\DatabaseBackup\Driver;

use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\DumpNotFoundException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\DumpNotReadableException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\EmptyDumpException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpDriverException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpFormatException;
use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use Deagel1337\Backup\Kit\Traits\CommandTrait;
use RuntimeException;

final class MariaDbBackupDriver implements DatabaseBackupDriver
{
    use CommandTrait;

    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly ProcessRunner $process = new ProcOpenProcessRunner
    ) {}

    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    public function createDump(?string $destination = null): DatabaseDump
    {
        if ($destination === null) {
            $destination = '/tmp/';
        }

        $directory = dirname($destination);

        if (! is_dir($directory)) {
            throw new RuntimeException(
                "Das Backup-Verzeichnis existiert nicht: {$directory}"
            );
        }

        if (! is_writable($directory)) {
            throw new RuntimeException(
                "Das Backup-Verzeichnis ist nicht beschreibbar: {$directory}"
            );
        }

        $path = $destination;

        $command = [
            'mariadb-dump',
            '--host='.$this->connection->host,
            '--port='.$this->connection->port,
            '--user='.$this->connection->username,
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
                .trim($result->errorOutput)
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
            throw new InvalidDumpDriverException('Der Dump gehört nicht zum MariaDB-Treiber.');
        }

        if (strtolower($dump->format) !== 'sql') {
            throw new InvalidDumpFormatException;
        }

        if (! $dump->exists()) {
            throw new DumpNotFoundException;
        }

        if (! $dump->isReadable()) {
            throw new DumpNotReadableException;
        }

        if ($dump->size() === 0) {
            throw new EmptyDumpException;
        }
    }

    public function restoreDump(DatabaseDump $dump): void
    {
        $command = [
            'mariadb',
            '--host='.$this->connection->host,
            '--port='.$this->connection->port,
            '--user='.$this->connection->username,
            $this->connection->database,
        ];

        $result = $this->process->run($command, ['MYSQL_PWD' => $this->connection->password], null, null, $dump->path);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das MariaDB-Dump konnte nicht wiederhergestellt werden: '.trim($result->errorOutput)
            );
        }
    }

    public function validateRequirements(): void
    {
        $missingCommands = [];

        foreach (['mariadb', 'mariadb-dump'] as $command) {
            if (! $this->isCommandAvailable($command)) {
                $missingCommands[] = $command;
            }
        }

        if ($missingCommands !== []) {
            throw new RuntimeException(
                'Folgende MariaDB-Programme sind nicht verfügbar: '
                .implode(', ', $missingCommands)
            );
        }
    }
}
