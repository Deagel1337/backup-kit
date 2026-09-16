<?php

namespace Restore\Context;

use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;

final class BackupContext
{
    public function __construct(
        public readonly string $destination,
        public ?DatabaseDump $dump = null,
        public ?ArchiveInfo $archive = null
    )
    {}
}