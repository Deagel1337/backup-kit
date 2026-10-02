<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;

final class BackupContext extends Context
{
    /**
     * @param  array<string>  $files
     */
    public function __construct(
        public string $destination = '',
        public ?DatabaseDump $dump = null,
        public ?ArchiveInfo $archive = null,
        private array $files = [],
    ) {}

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
