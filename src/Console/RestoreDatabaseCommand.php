<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Restore\RestoreMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpException;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDatabaseStep;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:restore',
    description: 'This command allows you to restore a mariadb database',
    usages: ['path/to/dump.sql'],
    help: <<<'HELP'
The <info>%command.name%</info> command restores a MariaDB database from an SQL dump.

  <info>%command.full_name% <path></info>
HELP
)]
final class RestoreDatabaseCommand
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    ) {}

    public function __invoke(
        #[Argument('Path to the SQL dump file to restore')] string $path,
        OutputInterface $output
    ): int {
        $connection = new DatabaseConnection(
            driver: 'mariadb',
            host: 'localhost',
            port: 6033,
            database: 'dev-db',
            username: 'devuser',
            password: 'devpass'
        );

        $dumpToRestore = new DatabaseDump($path, $connection->driver, 'sql');

        try {
            $dumpToRestore->validate();

            $steps = [
                new RestoreDatabaseStep($this->driver),
            ];

            $app = RestoreMariaDbApplication::create(
                driver: $this->driver,
                steps: $steps
            );

            $app->run($dumpToRestore);

            $output->writeln('Database Restore completed');
        } catch (InvalidDumpException $e) {

            $output->write($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
