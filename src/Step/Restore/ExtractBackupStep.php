<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;

final class ExtractBackupStep implements RestoreStep
{
    public function __construct(private readonly ArchiveDriver $archive, private readonly ?string $destination = null) {}

    public function name(): string
    {
        return 'Extrahiert die Backupdateien.';
    }

    public function execute(RestoreContext $context): void
    {
        (new RestoreArchiveStep($this->archive, $this->destination))->execute($context);
    }
}
