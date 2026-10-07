<?php

namespace Deagel1337\Backup\Kit\Application\Archive;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\Services\ArchiveService;

final class ArchiveApplication
{
    public function __construct(
        private readonly ArchiveService $service
    ) {}

    /**
     * @param  array<string>  $paths
     */
    public function run(array $paths, string $name): ArchiveInfo
    {
        return $this->service->createArchive($paths, $name);
    }

    /**
     * Lists the content of an archive
     *
     * @return iterable<ArchiveEntry>
     */
    public function list(ArchiveInfo $archive): iterable
    {
        return $this->service->listArchiveContent($archive);
    }

    /**
     * Lists all archives in the configured repository, or in the given repository when supported by the driver.
     *
     * @return iterable<ArchiveInfo>
     */
    public function listAllArchives(?string $repository = null): iterable
    {
        return $this->service->listArchives($repository);
    }

    /**
     * Extract an archive
     *
     * @param  array<string>  $paths
     */
    public function extract(
        ArchiveInfo $archiveInfo,
        string $destination,
        array $paths = [],
        int $stripComponents = 0,
    ): void {
        if (strcmp($destination, '') === 0) {
            $destination = '.';
        }

        if ($paths === [] && $stripComponents === 0) {
            $this->service->extractArchive($archiveInfo, $destination);

            return;
        }

        $this->service->extractArchive($archiveInfo, $destination, $paths, $stripComponents);
    }

    /**
     * Entfernt Archive, die von den Aufbewahrungsregeln nicht mehr abgedeckt werden.
     * Ob und wie die Regeln umgesetzt werden, hängt vom Treiber ab (Borg nutzt `borg prune`).
     *
     * @throws \InvalidArgumentException Bei ungültigen Regeln.
     * @throws \RuntimeException Wenn Archive nicht entfernt werden können.
     */
    public function prune(RetentionPolicy $policy): void
    {
        $this->service->prune(...$policy->toArray());
    }
}
