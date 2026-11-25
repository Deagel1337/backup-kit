<?php

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Backup\BackupMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Step\Backup\BackupDatabaseStep;
use Deagel1337\Backup\Kit\Step\Backup\CheckDiskSpaceStep;
use Deagel1337\Backup\Kit\Step\Backup\ValidateBackupContextStep;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:dump',
    description: 'Creates a database dump',
    usages: ['/tmp/dumps/'],
    help: <<<'HELP'
The <info>%command.name%</info> command dumps the database to <comment>backup.sql</comment> inside the destination.

  <info>%command.full_name% <destination></info>

The destination is prepended to the file name, so include a trailing slash (e.g. <comment>/tmp/dumps/</comment>).
HELP
)]
final class DumpDatabaseCommand
{
    public function __construct(
        private readonly DatabaseBackupDriver $driver,
    ) {}

    public function __invoke(
        #[Argument('Directory for the dump (with trailing slash); the file is named backup.sql')] string $destination,
        OutputInterface $output
    ): int {
        $steps = [
            new CheckDiskSpaceStep,
            new BackupDatabaseStep($this->driver),
            new ValidateBackupContextStep($this->driver),
        ];

        $app = BackupMariaDbApplication::create($steps);

        $dump = $app->run($destination.'backup.sql');

        if (! $dump->exists()) {
            $output->writeln('<error>Dump was not created.</error>');

            return Command::FAILURE;
        }

        if (! $dump->isReadable()) {
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
