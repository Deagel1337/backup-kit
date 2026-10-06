<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'borg:list')]
final class BorgListCommand
{
    public function __construct(
        private readonly ArchiveApplication $archive,
    ) {}

    public function __invoke(OutputInterface $output): int
    {
        try {
            /** @var string $repository */
            $repository = $_ENV['BORG_REPOSITORY'] ?? '';

            if ($repository === '') {
                throw new RuntimeException('No repsotiory in environment defined.');
            }

            $proccOutput = $this->archive->listAllArchives();

            /** @var ArchiveEntry $proccO */
            foreach ($proccOutput as $proccO) {
                $output->writeln(
                    sprintf(
                        '<info>%s</info>',
                        $proccO->path
                    )
                );
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('<error>Failed listening borg archives</error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
