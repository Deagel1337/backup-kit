<?php

namespace Deagel1337\Backup\Kit\Context;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\Context;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;

final class RestoreContext extends Context
{
    public function __construct(
        public ?ArchiveInfo $archive,
        public ?DatabaseDump $dump,
        public string $destination,
        public ?DatabaseDump $rollbackDump = null,
    )
    {
        parent::__construct(
            destination: $this->destination,
            dump: $this->dump,
            archive: $this->archive
        );
    }
}