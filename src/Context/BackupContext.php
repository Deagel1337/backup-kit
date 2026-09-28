<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

final class BackupContext
{
    public function __construct(
        public readonly string $destination = "",
        public ?DatabaseDump $dump = null,
        public ?ArchiveInfo $archive = null,
        private array $files = [],
    )
    {}
}