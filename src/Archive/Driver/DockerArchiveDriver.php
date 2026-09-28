<?php

namespace Backup\Php\Archive\Driver;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Context\DockerContainerContext;
use Backup\Php\Process\Interface\ProcessRunner;
use Backup\Php\Process\Runner\ProcOpenProcessRunner;
use Backup\Php\Traits\CommandTrait;
use Backup\Php\Traits\PathTrait;
use Override;
use RuntimeException;

final class DockerArchiveDriver implements ArchiveDriver
{
    use PathTrait;
    use CommandTrait;

    public function __construct(
        private readonly DockerContainerContext $context,
        private readonly ProcessRunner $process = new ProcOpenProcessRunner()
    )
    {
        if(trim($this->context->containerName) === '') {
            throw new RuntimeException('Es wurd kein Container Name übergeben');
        }

        if(trim($this->context->volumeName) === '') {
            throw new RuntimeException('Es wurde kein Volume-Name übergeben');
        }
    }

    protected function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    #[Override]
    public function createArchive(array $paths, string $archiveName): ArchiveInfo
    {
        // foreach($paths as $path) {
        //     if(!$this->doesPathExist($path)) {
        //         throw new RuntimeException(
        //             sprintf("Pfad: %s wurde nicht gefunden oder existiert nicht. Bitte überprüfen", $path)
        //         );
        //     }
        // }

        $archivePath = $this->context->destinationPath
            . DIRECTORY_SEPARATOR
            . basename($archiveName);

        $command = [
            'docker',
            'run',
            '--rm',
            '-v',
            sprintf(
                '%s:%s:ro',
                $this->context->volumeName,
                $this->context->dataPath,
            ),
            '-v',
            sprintf(
                '%s:/backup',
                $this->context->destinationPath,
            ),
            'busybox',
            'tar',
            '-czf',
            '/backup' . sprintf('%s.tar.gz', $archiveName),
            '-C',
            $this->context->dataPath,
            '.',
        ];

        $processResult = $this->process->run($command);

        if(!$processResult->successful()) {
            throw new RuntimeException(
                sprintf("Something went wrong: %s", $processResult->errorOutput)
            );
        }

        $newArchive = new ArchiveInfo($archivePath, 'docker', 'tar.gz');

        return $newArchive;
    }

    #[Override]
    public function listContent(ArchiveInfo $archive): string
    {
        $this->validateArchive($archive);

        $command = [
            'tar',
            '-ztvf',
            $archive->path,
        ];

        $result = $this->process->run($command);

        if(!$result->successful()) {
            throw new RuntimeException(
                'Der Inhalt des Docker-Archivs konnte nicht ausgelesen werden: '
                . trim($result->errorOutput)
            );

        }
    
        return $result->output;
    }

    #[Override]
    public function validateArchive(ArchiveInfo $archive): void
    {
        if(!file_exists($archive->path)) {
            throw new RuntimeException("Die Archiv-Datei existiert nicht.");
        }
    }

    #[Override]
    public function validateRequirements(): void
    {
        $requiredPrograms = [
            'docker',
        ];

        foreach($requiredPrograms as $program) {
            if(!$this->isCommandAvailable($program)) {
                throw new RuntimeException("Docker wurde nicht installiert!");
            }
        }

    }

    #[Override]
    public function extractArchive(ArchiveInfo $archive, string $destination): void
    {
        $this->validateArchive($archive);

        if(!is_dir($destination)) {
            throw new RuntimeException(
                sprintf("Das Zielverzeichnis existiert nicht: %s}", $destination)
            );
        }

        $archiveDirectory = dirname($archive->path);
        $archiveName = basename($archive->path);

        $command = [
            'docker',
            'run',
            '--rm',
            '-v',
            sprintf(
                '%s:%s',
                $this->context->volumeName,
                $destination
            ),
            '-v',
            sprintf(
                '%s:/backup:ro',
                $archiveDirectory
            ),
            'busybox',
            'tar',
            '-xzf',
            '/backup/' . $archiveName,
            '-C',
            $destination
        ];

        $result = $this->process->run($command);

        if(!$result->successful()) {
            throw new RuntimeException(
                'Das Docker-Archiv konnte nicht entpackt werden: ' . trim($result->errorOutput)
            );
        }
    }
}