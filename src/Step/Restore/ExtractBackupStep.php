<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class ExtractBackupStep implements RestoreStep
{
    public function __construct(private readonly ArchiveDriver $archive, private readonly string $destination = "") {}
    public function name(): string
    {
        return 'Extrahiert die Backupdateien.';
    }

    public function execute(RestoreContext $context): void
    {
        if(!$context->archive) {
            throw new RuntimeException("No Archive found");
        }
        
        $this->archive->extractArchive($context->archive, $this->destination);
    }
}