<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class ExtractBackupStep implements RestoreStep
{
    public function __construct(private readonly ArchiveDriver $archive, private readonly string $destination = "") {}
    public function name(): string
    {
        return 'Extrahiert die Backupdateien.';
    }

    public function execute(RestoreContext $context): void
    {
        $this->archive->extractArchive($context->archive, $this->destination);
    }
}