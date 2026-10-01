<?php

namespace Deagel1337\Backup\Kit\Archive\Driver;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use Deagel1337\Backup\Kit\Traits\CommandTrait;
use Deagel1337\Backup\Kit\Traits\PathTrait;
use Override;
use RuntimeException;

final class BorgArchiveDriver implements ArchiveDriver
{
    use CommandTrait;
    use PathTrait;

    public function __construct(
        private readonly string $repository,
        private readonly string $passphrase = '',
        private readonly ?string $sshKeyPath = null,
        private readonly ?int $sshPort = null,
        private readonly ProcessRunner $process = new ProcOpenProcessRunner(),
    ) {
        if (trim($this->repository) === '') {
            throw new RuntimeException('Es wurde kein Borg-Repository angegeben.');
        }

        if ($this->sshKeyPath !== null && !is_readable($this->sshKeyPath)) {
            throw new RuntimeException('Der SSH-Key ist unter "' . $this->sshKeyPath . '" nicht lesbar.');
        }
    }

    /**
     * Needs to be implemented for the CommandTrait
     * @return ProcessRunner
     */
    #[Override]
    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    /**
     * @return array<string>
     */
    private function rshOption(): array
    {
        if ($this->sshKeyPath === null && $this->sshPort === null) {
            return [];
        }

        $sshCommand = 'ssh';

        if ($this->sshPort !== null) {
            $sshCommand .= ' -p ' . escapeshellarg((string) $this->sshPort);
        }

        if ($this->sshKeyPath !== null) {
            $sshCommand .= ' -i ' . escapeshellarg($this->sshKeyPath);
        }

        return ['--rsh', $sshCommand];
    }

    /**
     * Returns the contents of the archive
     * @param ArchiveInfo $archive
     * @throws RuntimeException
     * @return iterable<ArchiveEntry>
     */
    #[Override]
    public function listArchive(ArchiveInfo $archive): iterable
    {
        $command = [];
        $format = '{path}{TAB}{size}{TAB}{type}{NL}';

        if(str_contains($archive->path, $this->repository)) {
            $command = array_merge(
                ['borg', 'list'],
                $this->rshOption(),
                ['--format', $format],
                [$archive->path]
            );
        } else if(strcmp($this->repository, $archive->path) != 0) {
            $command = array_merge(
                ['borg', 'list'], 
                $this->rshOption(),
                ['--format', $format],
                [sprintf("%s::%s",$this->repository, $archive->path)]
            );
        } else {
            $command = array_merge(
                ['borg', 'list'],
                $this->rshOption(),
                ['--format', $format],
                [sprintf("%s", $this->repository)]
            );
        }

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if(!$result->successful()) {
            throw new RuntimeException('Beim Ausführen des Prozesses ist etwas schiefgelaufen: ' . trim($result->errorOutput));
        }


        foreach(explode("\n", trim($result->output)) as $line) {
            if ($line === '') {
                continue;
            }

            [$path, $size, $type] = explode("\t", $line, 3);

            $type = $this->parseType($type);

            yield new ArchiveEntry(
                path: $path,
                size: (int) $size,
                type: $type
            );
        }
    }

    /**
     * Prases the type of the entry
     * @param string $mode
     * @return ArchiveEntryType
     */
    private function parseType(string $mode): ArchiveEntryType {
        return match ($mode) {
            '-' => ArchiveEntryType::File,
            'd' => ArchiveEntryType::Directory,
            'l' => ArchiveEntryType::Symlink,
            default => ArchiveEntryType::Undefined 
        };
    }

    /**
     * Gives an borg archive entry
     * @throws RuntimeException
     * @return iterable<ArchiveInfo>
     */
    #[Override]
    public function listArchives(): iterable
    {
        $command = array_merge(
            ['borg', 'list'],
            $this->rshOption(),
            [$this->repository],
        );

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if(!$result->successful()) {
            throw new RuntimeException(
                sprintf("<error>Auflistung fehlgeschlagen: %s</error>", $this->repository)
            );
        }

        foreach(explode("\n", trim($result->output)) as $line) {
            if($line === '') {
                continue;
            }

            $parts = preg_split('/\s{2,}/', trim($line));

            if($parts == false) {
                continue;
            }

            yield new ArchiveInfo(
                path: $this->repository . '::' . $parts[0],
                driver: 'borg',
                format: 'borg'
            );
        }
    }

    /**
     * Creates an Archive
     * @param array<string> $paths
     * @param string $archiveName 
     * @throws RuntimeException
     * @return ArchiveInfo
     */
    #[Override]
    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        foreach ($paths as $path) {
            if (!$this->doesPathExist($path)) {
                throw new RuntimeException(
                    sprintf("<error>Invalider Pfad entdeckt: %s</error>", $path)
                );
            }
        }

        $target = $this->repository . '::' . $archiveName;

        $command = array_merge(['borg', 'create'], $this->rshOption(), ['--stats', $target], $paths);

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);
        
        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Borg-Archiv konnte nicht erstellt werden: ' . trim($result->errorOutput)
            );
        }

        return new ArchiveInfo($target, 'borg', 'borg');
    }

    /**
     * Validates the archive.
     * @param ArchiveInfo $archive
     * @throws RuntimeException
     * @return void
     */
    #[Override]
    public function validateArchive(ArchiveInfo $archive): void
    {
        if ($archive->driver !== 'borg') {
            throw new RuntimeException('Kein Borg-Archiv.');
        }
    }

    /**
     * Extracts the content to a given destination.
     * @param ArchiveInfo $archive
     * @param string $destination
     * @throws RuntimeException
     * @return void
     */
    #[Override]
    public function extractArchive(ArchiveInfo $archive, string $destination = '.'): void 
    {
        $this->validateArchive($archive);

        if (!is_dir($destination)) {
            if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
                throw new RuntimeException(
                    'Restore-Ziel konnte nicht erstellt werden: '
                    . $destination
                );
            }
        }

        $command = array_merge(
            [
                'borg',
                'extract',
                $archive->path,
            ],
            $this->rshOption()
        );

        $result = $this->process->run(
            $command,
            [
                'BORG_PASSPHRASE' => $this->passphrase,
            ],
            $destination
        );

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Borg Restore fehlgeschlagen: '
                . trim($result->errorOutput)
            );
        }
    }

    /**
     * Validates the integrity of the archive.
     * @throws RuntimeException
     * @return void
     */
    #[Override]
    public function validateRequirements(): void
    {
        if (!$this->isCommandAvailable('borg')) {
            throw new RuntimeException('Das Programm borg ist nicht verfügbar.');
        }

        $command = array_merge(['borg', 'list'], $this->rshOption(), [$this->repository]);

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Borg-Repository konnte nicht erreicht werden: ' . trim($result->errorOutput)
            );
        }
    }
}