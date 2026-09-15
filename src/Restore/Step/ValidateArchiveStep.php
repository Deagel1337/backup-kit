<?php

namespace Restore\Step;

use Archive\Driver\ArchiveDriver;
use Restore\Model\RestoreContext;
use Restore\Interfaces\RestoreStep;

final class ValidateArchiveStep implements RestoreStep
{
    public function __construct(
        private readonly ArchiveDriver $archiveDriver
    )
    { }

    public function execute(RestoreContext $context): void
    {
        $this->archiveDriver->validateArchive($context->archive);
    }
}