<?php

declare(strict_types=1);

namespace Backup\Php\Console;

use Backup\Php\Application\Restore\RestoreMariaDbApplication;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Exception\DumpDriverException\InvalidDumpException;
use Backup\Php\Step\Restore\RestoreDatabaseStep;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:restore, --dump',
    description: 'This command allows you to restore a mariadb database',
    usages: ['--dump path/to/dump.sql ']
)]
final class RestoreDatabaseCommand
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    ) { }

    public function __invoke(
        #[Argument('Path to database dump')] string $path,
        OutputInterface $output
    ): int
    {
        $connection = new DatabaseConnection(
            driver: 'mariadb',
            host: 'localhost',
            port: 6033,
            database: 'dev-db',
            username: 'devuser',
            password: 'devpass'
        );

        $dumpToRestore = new DatabaseDump($path, $connection->driver, "sql");
        
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

            $output->writeln("Database Restore completed");
        } catch(InvalidDumpException $e) {

            $output->write($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
