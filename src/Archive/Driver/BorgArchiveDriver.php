<?php

namespace Archive\Driver;

use Archive\Driver\ArchiveDriver;
use Archive\Model\ArchiveInfo;
use Process\ProcessRunner\ProcessRunner;
use Process\Runner\ProcOpenProcessRunner;
use Src\Traits\CommandTrait;
use Src\Traits\PathTrait;
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
    private function processRunner(): ProcessRunner
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

    public function listArchiveContent(string $backupName): void 
    {
        $command = array_merge(['borg', 'list'], $this->rshOption(), [$this->repository, $backupName]);

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if($result->successful()) {
            echo $result->output;
        }

        if($result->exitCode !== 0) {
            throw new RuntimeException('Beim Ausführen des Prozesses ist etwas schiefgelaufen: ' . trim($result->errorOutput));
        }
    }

    public function listContent(ArchiveInfo $archive): void
    {
        $command = array_merge(['borg', 'list'], $this->rshOption(), [$this->repository, $archive->path]);

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase]);

        if($result->successful()) {
            echo $result->output;
        }

        if($result->exitCode !== 0) {
            throw new RuntimeException('Beim Ausführen des Prozesses ist etwas schiefgelaufen: ' . trim($result->errorOutput));
        }
    }

    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        foreach ($paths as $path) {
            if (!$this->doesPathExist($path)) {
                throw new RuntimeException(
                    "Invalider Pfad entdeckt: {$path}"
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

    public function extractArchive(ArchiveInfo $archive, string $destination): void
    {
        $this->validateArchive($archive);

        $command = array_merge(['borg', 'extract'], $this->rshOption(), [$archive->path]);

        $result = $this->process->run($command, ['BORG_PASSPHRASE' => $this->passphrase], $destination);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Borg-Archiv konnte nicht entpackt werden: ' . trim($result->errorOutput)
            );
        }
    }

    public function validateRequirements(): void
    {
        if (!$this->isCommandAvailable('borg')) {
            throw new RuntimeException('Das Programm borg ist nicht verfügbar.');
        }
    }
}