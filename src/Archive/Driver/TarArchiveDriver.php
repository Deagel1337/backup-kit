<?php

namespace Archive\Driver\TarArchiveDriver;

use Archive\Driver\ArchiveDriver;
use Archive\Model\ArchiveInfo;
use RuntimeException;

final class TarArchiveDriver implements ArchiveDriver
{
    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($archiveName);

        $command = array_merge(['tar', '-czf', $path], $paths);

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Der Prozess tar konnte nicht gestartet werden.');
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Das Tar-Archiv konnte nicht erstellt werden: ' . trim($errorOutput));
        }

        return new ArchiveInfo($path, 'tar', 'tar.gz');
    }

    public function validateArchive(ArchiveInfo $archive): void
    {
        if ($archive->driver !== 'tar') {
            throw new RuntimeException('Kein Tar-Archiv.');
        }

        if (!$archive->exists()) {
            throw new RuntimeException('Die Archiv-Datei existiert nicht.');
        }
    }

    public function extractArchive(ArchiveInfo $archive, string $destination): void
    {
        $this->validateArchive($archive);

        $command = ['tar', '-xzf', $archive->path, '-C', $destination];

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Der Prozess tar konnte nicht gestartet werden.');
        }

        fclose($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException('Das Tar-Archiv konnte nicht entpackt werden: ' . trim($errorOutput));
        }
    }

    public function validateRequirements(): void
    {
        if (!$this->isCommandAvailable('tar')) {
            throw new RuntimeException('Das Programm tar ist nicht verfügbar.');
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