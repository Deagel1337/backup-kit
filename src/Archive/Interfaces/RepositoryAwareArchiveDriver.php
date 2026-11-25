<?php

namespace Deagel1337\Backup\Kit\Archive\Interfaces;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;

interface RepositoryAwareArchiveDriver extends ArchiveDriver
{
    /**
     * @return iterable<ArchiveInfo>
     */
    public function listArchivesFromRepository(string $repository): iterable;
}
