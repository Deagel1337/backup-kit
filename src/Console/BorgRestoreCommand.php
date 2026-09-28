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
        protected static $defaultName = 'borg:restore';

        public function __construct(
            private readonly ArchiveApplication $archive,
            private readonly ArchiveInfo $repository
        ) {
            parent::__construct();
        }

        protected function configure(): void
        {
            $this->addArgument(
                'destination',
                InputArgument::REQUIRED,
                'Directory where the backup should be restored.'  
            );
        }

        protected function execute(
            InputInterface $input,
            OutputInterface $output,
        ): int {
            $io = new SymfonyStyle($input, $output);

            $destination = (string) $input->getArgument('destination');

            $content = $this->archive->list(
                $this->repository
            );

            $backups = $this->parseBackups($content);

            if ($backups === []) {
                $io->error('Keine Backups gefunden.');

                return Command::FAILURE;
            }

            $selected = $io->choice(
                'Backup auswählen',
                $backups,
            );

            $archive = new ArchiveInfo(
                path: $this->repository->path . '::' . $selected,
                driver: 'borg',
                format: 'borg',
            );

            $io->info([
                'Backup: ' . $selected,
                'Destination: ' . $destination,
            ]);

            $this->archive->extract(
                archiveInfo: $archive,
                destination: $destination,
            );

            $io->success('Restore erfolgreich');

            return Command::SUCCESS;
        }

        /**
         * @return array<string>
         */
        private function parseBackups(string $output): array
        {
            return array_values(
                array_filter(
                    array_map(
                        static fn (string $line): string => trim($line),
                        explode("\n", $output),
                    ),
                    static fn (string $line): bool => $line !== '',
                ),
            );
        }
}