<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;

class Context
{
    public function __construct(
        public string $destination,
        public ?DatabaseDump $dump = null,
        public ?ArchiveInfo $archive = null
    ) {}
}
