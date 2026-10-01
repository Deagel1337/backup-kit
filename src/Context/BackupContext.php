<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

final class BackupContext extends Context
{
    /**
     * @param string $destination
     * @param DatabaseDump | null $dump
     * @param ArchiveInfo | null $archive
     * @param array<string> $files
     */
    public function __construct(
        public string $destination = "",
        public ?DatabaseDump $dump = null,
        public ?ArchiveInfo $archive = null,
        private array $files = [],
    )
    {}

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}