<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Throwable;

#[AsCommand(
    name: 'borg:list-content',
    description: 'List the files contained in a Borg archive',
    usages: ['/path/to/repo::wordpress-2026-10-07'],
    help: <<<'HELP'
The <info>%command.name%</info> command lists the content of one archive.

  <info>%command.full_name% <archiveName></info>

Use the full archive path as shown by <info>borg:list</info> (repository::archive).
HELP
)]
final class BorgListArchiveContentCommand
{
    public function __construct(
        private readonly ArchiveApplication $archive,
    ) {}

    public function __invoke(OutputInterface $output, #[Argument('Full archive path (repository::archive), see borg:list')] string $archiveName): int
    {
        try {
            /** @var string $repository */
            $repository = $_ENV['BORG_REPOSITORY'] ?? '';

            if($repository === '') {
                throw new RuntimeException('No repository in environment defined.');
            }

            $archive = new ArchiveInfo(
                $archiveName,
                'borg',
                'borg'
            );

            $proccOutput = $this->archive->list($archive);

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
        } catch(Throwable $e) {
            $output->writeln('<error>Failed listening borg archives</error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}