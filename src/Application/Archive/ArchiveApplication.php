<?php

namespace Deagel1337\Backup\Kit\Application\Archive;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Services\ArchiveService;

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

    public function list(ArchiveInfo $archive): string
    {
        return $this->service->listArchiveContent($archive);
    }

    public function extract(ArchiveInfo $archiveInfo, string $destination): void
    {
        $this->service->extractArchive($archiveInfo, $destination);
    }
}
