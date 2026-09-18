<?php

namespace Backup\Php\Context;

use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Archive\Model\ArchiveInfo;

final class RestoreContext
{
    public function __construct(
        public ArchiveInfo $archive,
        public DatabaseDump $dump,
        public string $destination,
    )
    {}
}