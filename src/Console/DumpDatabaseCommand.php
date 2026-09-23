<?php

namespace Backup\Php\Console;

use Backup\Php\Application\BackupMariaDbApplication;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\Step\Backup\BackupDatabaseStep;
use Backup\Php\Step\Backup\CheckDiskSpaceStep;
use Backup\Php\Step\Backup\ValidateBackupContextStep;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:dump, --destination',
    description: 'Creates a database dump',
)]
final class DumpDatabaseCommand
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    )
    { }

    public function __invoke(
        #[Argument('Database dump destination')] string $destination,
        OutputInterface $output
    ): int
    {
        $steps = [
            new CheckDiskSpaceStep(),
            new BackupDatabaseStep($this->driver),
            new ValidateBackupContextStep($this->driver),
        ];

        $app = BackupMariaDbApplication::create($steps);

        $dump = $app->run($destination . 'backup.sql');

        if (!$dump->exists()) {
            $output->writeln('<error>Dump was not created.</error>');
            return Command::FAILURE;
        }

        if (!$dump->isReadable()) {
            $output->writeln('<error>Dump is not readable.</error>');
            return Command::FAILURE;
        }

        if ($dump->size() <= 0) {
            $output->writeln('<error>Dump is empty.</error>');
            return Command::FAILURE;
        }

        $output->writeln(
            sprintf(
                '<info>Database dump created successfully: %s (%d bytes)</info>',
                $dump->path,
                $dump->size()
            )
        );

        return Command::SUCCESS;
    }
}