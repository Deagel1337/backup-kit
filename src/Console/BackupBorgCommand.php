<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Throwable;

#[AsCommand(name: 'borg:archive, --name --paths')]
final class BackupBorgCommand
{
    public function __construct(
        private readonly ArchiveApplication $archive,
    ) {}

    public function __invoke(
        #[Argument('Name of the archive')] string $name,
        #[Argument('Paths to files or direcotries that needed to be archived')] array $paths,
        OutputInterface $output
    ): int
    {
        try {

            if($paths === []) {
                throw new RuntimeException(
                    'No files or directories were specified.'
                );
            }

            if($name === '') {
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
        } catch(Throwable $e) {
            $output->writeln("<error>Failed to archive files: </error>");
            $output->writeln("<error>" . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}