<?php

namespace Deagel1337\Backup\Kit\Context;


use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

class Context
{
    public function __construct(
        public string $destination,
        public ?DatabaseDump $dump = null,
        public ?ArchiveInfo $archive = null
    )
    { }
}