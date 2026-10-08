<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use RuntimeException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'borg:archive',
    description: 'Create a Borg archive from files or directories',
    usages: ['wordpress-2026-10-07 /var/lib/docker/volumes/wp/_data/wp-content /etc/nginx'],
    help: <<<'HELP'
The <info>%command.name%</info> command creates a new archive in the configured Borg repository (BORG_REPOSITORY).

  <info>%command.full_name% <name> <paths>...</info>

Pass the archive name first, followed by one or more files or directories.
HELP
)]
final class BackupBorgCommand
{
    public function __construct(
        private readonly ArchiveApplication $archive,
    ) {}

    /**
     * @param  array<string>  $paths
     *
     * @throws RuntimeException
     */
    public function __invoke(
        #[Argument('Name of the new archive')] string $name,
        #[Argument('One or more files or directories to archive')] array $paths,
        OutputInterface $output
    ): int {
        try {

            if ($paths === []) {
                throw new RuntimeException(
                    'No files or directories were specified.'
                );
            }

            if ($name === '') {
                throw new RuntimeException(
                    'No archive name was specified'
                );
            }

            $createdArchive = $this->archive->run($paths, $name);

            $output->writeln(
                sprintf(
                    '<info>Borg archive created successfully: %s</info>',
                    $createdArchive->path
                )
            );
        } catch (Throwable $e) {
            $output->writeln('<error>Failed to archive files: </error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
