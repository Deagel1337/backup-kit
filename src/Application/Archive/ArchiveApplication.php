<?php

namespace Deagel1337\Backup\Kit\Application\Archive;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
final class ArchiveApplication
{
    public function __construct(
        private readonly ArchiveService $service
    ) {}

    /**
     * @param array<string> $paths
     * @param string $name
     */
    public function run(array $paths, string $name): ArchiveInfo
    {
        return $this->service->createArchive($paths, $name);
    }

    /**
     * Lists the content of an archive
     * @param ArchiveInfo $archive
     * @return iterable<ArchiveEntry>
     */
    public function list(ArchiveInfo $archive): iterable
    {
        return $this->service->listArchiveContent($archive);
    }

    /**
     * Lists all archives in a directory or a different structure. It really depends on the driver implementation
     * @return iterable<ArchiveInfo>
     */
    public function listAllArchives(): iterable
    {
        return $this->service->listArchives();
    }

    /**
     * Extract an archive
     * @param ArchiveInfo $archiveInfo
     * @param string $destination
     * @return void
     */
    public function extract(ArchiveInfo $archiveInfo, string $destination): void
    {
        if(strcmp($destination, '') === 0) {
            $destination = '.';
        }

        $this->service->extractArchive($archiveInfo, $destination);
    }
}
