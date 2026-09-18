<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;
use Backup\Php\Archive\Interfaces\ArchiveDriver;

final class ValidateArchiveStep implements RestoreStep
{
    public function __construct(
        private readonly ArchiveDriver $archiveDriver
    )
    { }

    public function name(): string
    {
        return "Archiv valiederen";
    }

    public function execute(RestoreContext $context): void
    {
        $this->archiveDriver->validateArchive($context->archive);
    }
}