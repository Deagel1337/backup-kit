<?php

declare(strict_types=1);

namespace Backup\Php\Console;

use Backup\Php\Application\Archive\ArchiveApplication;
use Backup\Php\Traits\PathTrait;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'docker:volume:backup')]
final class ArchiveDockerVolumeCommand extends Command
{
    use PathTrait;

    public function __construct(
        private readonly ArchiveApplication $archive,
    )
    {
        parent::__construct();
    }

    public function __invoke(
        #[Argument('Docker Volume name')] string $volumeName,
        #[Argument('Destination for the backup')] string $destination,
        OutputInterface $output
    ): int
    {
        if(!$this->doesPathExist($destination)) {
            $output->writeln(
                sprintf("<error>Destination path is either missing or doesnt exist: %s</error>", $destination)
            );

            return Command::FAILURE;
        }

        try {
            $archiveName = trim($volumeName . '_backup');
            $createdArchiv = $this->archive->run(['./wp-content'], $archiveName);

            if(!$createdArchiv->exists()) {
                $output->writeln(sprintf("Failed creating archive: %s", $createdArchiv->path));
            }

            $output->writeln("<info>" . $this->archive->list($createdArchiv) . "</info>");

            return Command::SUCCESS;
        } catch(\Throwable $e) {
            $output->writeln(
                sprintf("<error>Failed to backup docker volume: %s\n%s</error>", $volumeName, $e->getMessage())
            );
            return Command::FAILURE;
        }
    }
}