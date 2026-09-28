<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

final class RestoreContext
{
    public function __construct(
        public ArchiveInfo $archive,
        public DatabaseDump $dump,
        public string $destination,
        public ?DatabaseDump $rollbackDump = null,
    )
    {}
}