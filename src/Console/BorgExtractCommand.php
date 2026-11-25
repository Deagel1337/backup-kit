<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'borg:extract', description: 'Extract an archive from a Borg repository')]
final class BorgExtractCommand extends Command
{
    protected static string $defaultName = 'borg:extract';

    public function __construct(
        private readonly ArchiveApplication $archive,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<'HELP'
The <info>%command.name%</info> command lets you pick an archive and extracts it into the destination.

Borg stores paths without a leading slash, so a Docker volume is archived as
<comment>var/lib/docker/volumes/<name>/_data/...</comment>. Combine <info>--path</info> and
<info>--strip-components</info> to restore only a sub-directory directly into a new volume:

  <info>%command.full_name% /var/lib/docker/volumes/wp_new/_data \
    --path var/lib/docker/volumes/OLD/_data/wp-content --strip-components 7</info>

With <comment>--strip-components 6</comment> the <comment>wp-content</comment> directory itself is kept.
HELP);
        $this->addUsage('--repository ssh://user@host:23/./wordpress /tmp/restore');
        $this->addUsage('-p var/www/html/wp-content --strip-components 4 /tmp/restore');
        $this->addArgument(
            'destination',
            InputArgument::OPTIONAL,
            'Directory where the archive should be extracted (created if missing)',
            '.'
        );
        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Only extract this path (as stored in the archive, without leading slash); repeatable'
        );
        $this->addOption(
            'strip-components',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of leading path components to remove from extracted paths',
            '0'
        );
        $this->addOption(
            'repository',
            null,
            InputOption::VALUE_REQUIRED,
            'Borg repository to list archives from (default: BORG_REPOSITORY)'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        /** @var string $destination */
        $destination = $input->getArgument('destination');
        /** @var string|null $repository */
        $repository = $input->getOption('repository');

        /** @var array<string> $paths */
        $paths = $input->getOption('path');
        /** @var string $strip */
        $strip = $input->getOption('strip-components');

        if (! ctype_digit($strip)) {
            $io->error('--strip-components muss eine nicht-negative Ganzzahl sein.');

            return Command::FAILURE;
        }

        try {
            $archives = [];

            /** @var ArchiveInfo $archive */
            foreach ($this->archive->listAllArchives($repository) as $archive) {
                $archives[$archive->path] = $archive;
            }

            if ($archives === []) {
                $io->error('Keine Archive im Repository gefunden.');

                return Command::FAILURE;
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
                paths: $paths,
                stripComponents: (int) $strip,
            );

            $io->info([
                'Backup: '.$archive->path,
                'Destination: '.$destination,
            ]);
            
            $io->success('Extraktion erfolgreich');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
