<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;

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