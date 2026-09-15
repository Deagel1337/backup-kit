<?php

namespace Restore\Step;

use Archive\Driver\ArchiveDriver;
use Restore\Model\RestoreContext;
use Restore\Interfaces\RestoreStep;

final class RestoreArchiveStep implements RestoreStep
{
    public function __construct(
        private readonly ArchiveDriver $driver,
    )
    {}

    public function execute(RestoreContext $context): void
    {
        $this->driver->extractArchive($context->archive, $context->destination);
    }
}