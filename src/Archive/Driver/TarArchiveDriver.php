<?php

namespace Backup\Php\Archive\Driver;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Process\Interface\ProcessRunner;
use Backup\Php\Process\Runner\ProcOpenProcessRunner;
use Backup\Php\Traits\CommandTrait;
use RuntimeException;

final class TarArchiveDriver implements ArchiveDriver
{
    use CommandTrait;

    public function __construct(
        private readonly ProcessRunner $process = new ProcOpenProcessRunner(),
    )
    {}

    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($archiveName);

        $command = array_merge(['tar', '-czf', $path], $paths);

        $result = $this->process->run($command);

        if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Tar-Archiv konnte nicht entpackt werden: ' . trim($result->errorOutput)
            );
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

        $result = $this->process->run($command);

         if ($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Tar-Archiv konnte nicht entpackt werden: ' . trim($result->errorOutput)
            );
        }
    }

    public function listContent(ArchiveInfo $archive): void
    {
        $this->validateArchive($archive);

        $command = ['tar', '-ztvf', $archive->path];

        $result = $this->process->run($command);

        if($result->exitCode !== 0) {
            throw new RuntimeException(
                'Das Tar-Archiv konnte nicht gezeigt werden.'
            );
        }
    }

    public function validateRequirements(): void
    {
        if (!$this->isCommandAvailable('tar')) {
            throw new RuntimeException('Das Programm tar ist nicht verfügbar.');
        }
    }
}