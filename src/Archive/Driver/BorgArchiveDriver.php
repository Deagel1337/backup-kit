<?php

namespace Archive\Driver;

use Archive\Driver\ArchiveDriver;
use Archive\Model\ArchiveInfo;
use RuntimeException;

final class BorgArchiveDriver implements ArchiveDriver
{
    public function __construct(
        private readonly string $repository,
        private readonly string $passphrase = '',
        private readonly ?string $sshKeyPath = null,
        private readonly ?int $sshPort = null,
    ) {
        if (trim($this->repository) === '') {
            throw new RuntimeException('Es wurde kein Borg-Repository angegeben.');
        }

        if ($this->sshKeyPath !== null && !is_readable($this->sshKeyPath)) {
            throw new RuntimeException('Der SSH-Key ist unter "' . $this->sshKeyPath . '" nicht lesbar.');
        }
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

    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        $target = $this->repository . '::' . $archiveName;

        $command = array_merge(['borg', 'create'], $this->rshOption(), ['--stats', $target], $paths);

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['BORG_PASSPHRASE' => $this->passphrase],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Der Prozess borg konnte nicht gestartet werden.');
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Das Borg-Archiv konnte nicht erstellt werden: ' . trim($errorOutput));
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

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $destination,
            ['BORG_PASSPHRASE' => $this->passphrase],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Der Prozess borg konnte nicht gestartet werden.');
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Das Borg-Archiv konnte nicht entpackt werden: ' . trim($errorOutput));
        }
    }

    public function validateRequirements(): void
    {
        if (!$this->isCommandAvailable('borg')) {
            throw new RuntimeException('Das Programm borg ist nicht verfügbar.');
        }
    }

    private function isCommandAvailable(string $command): bool
    {
        $process = proc_open([$command, '--version'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            return false;
        }

        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($process) === 0;
    }
}