<?php

namespace Restore\Model;

use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;

final class RestoreContext
{
    public function __construct(
        public ArchiveInfo $archive,
        public DatabaseDump $dump,
        public string $destination,
    )
    {}
}