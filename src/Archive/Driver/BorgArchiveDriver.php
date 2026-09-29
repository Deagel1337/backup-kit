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

    // Damit das Trait auch den gleichen ProcessRunner nutzen kann oder auch einen anderen Runner
    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    /**
     * @return string[]
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

    public function listRepositoryBackups(): void
    {
        $command = array_merge(['borg', 'list'], $this->rshOption(), [$this->repository]);

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if($result->successful()) {
            echo $result->output;
        }

        if($result->exitCode !== 0) {
            throw new RuntimeException('Beim Ausführen des Prozesses ist etwas schiefgelaufen: ' . trim ($result->errorOutput));
        }
    }

    public function listArchiveContent(string $backupName): iterable 
    {
        $borgBackup = sprintf(
            '%s::%s',
            $this->repository,
            $backupName
        );

        $command = [
            'borg',
            'list',
            $this->rshOption(),
            $borgBackup
        ];

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if(!$result->successful()) {
            throw new RuntimeException('Beim Ausführen des Prozesses ist etwas schiefgelaufen: ' . trim($result->errorOutput));
        }

        foreach($this->parseArchiveEntries($result->output) as $entry) {
            yield $entry;
        }
    }

    private function parseArchiveEntries(string $output): iterable
    {
        foreach(explode("\n", trim($output)) as $line) {
            if($line === '') {
                continue;
            }

            $data = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

            yield new ArchiveEntry(
                path: $data['path'],
                size: (int) $data['size'],
                type: $data['type']
            );
        }
    }

    /**
     * Gibt die Einträge eines Archives zurück
     * @param ArchiveInfo $archive
     * @throws RuntimeException
     * @return iterable<ArchiveEntry>
     */
    public function listArchive(ArchiveInfo $archive): iterable
    {
        $command = [];

        if(strcmp($this->repository, $archive->path) != 0) {
            $command = array_merge(
                ['borg', 'list'], 
                $this->rshOption(), 
                [sprintf("%s::%s",$this->repository, $archive->path)]
            );
        } else {
            $command = array_merge(
                ['borg', 'list'],
                $this->rshOption(),
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

            yield new ArchiveEntry(
                path: $path,
                size: (int) $size,
                type: ArchiveEntryType::from($type)
            );
        }
    }

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

            if($parts == false || !isset($parts[0])) {
                continue;
            }

            yield new ArchiveInfo(
                path: $this->repository . '::' . $parts[0],
                driver: 'borg',
                format: 'borg'
            );
        }
    }

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

    public function validateArchive(ArchiveInfo $archive): void
    {
        if ($archive->driver !== 'borg') {
            throw new RuntimeException('Kein Borg-Archiv.');
        }
    }

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