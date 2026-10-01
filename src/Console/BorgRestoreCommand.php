<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'borg:restore')]
final class BorgRestoreCommand extends Command
{
        protected static string $defaultName = 'borg:restore';

        public function __construct(
            private readonly ArchiveApplication $archive,
        ) {
            parent::__construct();
        }

        protected function configure(): void
        {
            $this->addArgument(
                'destination',
                InputArgument::OPTIONAL,
                'Directory where the backup should be restored.'  
            );
        }

        protected function execute(
            InputInterface $input,
            OutputInterface $output,
        ): int {
            $io = new SymfonyStyle($input, $output);

            /** @var string $destination */
            $destination = $input->getArgument('destination');

            $archives = [];

            /** @var ArchiveInfo $archive */
            foreach($this->archive->listAllArchives() as $archive) {
                $archives[$archive->path] = $archive;
            }

            /** @var string $selectedIndex */
            $selectedIndex = $io->choice(
                'Backup auswählen',
                array_keys($archives),
            );

            $archive = $archives[$selectedIndex];

            $this->archive->extract(
                archiveInfo: $archive,
                destination: $destination,
            );

            $io->info([
                'Backup: ' . $archive->path,
                'Destination: ' . $destination,
            ]);

            $io->success('Restore erfolgreich');

            return Command::SUCCESS;
        }
}