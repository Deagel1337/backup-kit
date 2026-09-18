<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class CheckBackupExistsStep implements RestoreStep
{
    public function __construct(private readonly ArchiveDriver $archive) {}

    public function name(): string
    {
        return 'Überprüf, ob das Backup vorhanden ist.';
    }

    public function execute(RestoreContext $context): void
    {
        $this->archive->listContent($context->archive);
    }
}