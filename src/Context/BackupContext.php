<?php

namespace Backup\Php\Context;

use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Archive\Model\ArchiveInfo;

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