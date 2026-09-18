<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;
use Backup\Php\Archive\Interfaces\ArchiveDriver;

final class RestoreArchiveStep implements RestoreStep
{
    public function __construct(
        private readonly ArchiveDriver $driver,
    )
    {}

    public function name(): string
    {
        return "Archiv wiederherstellen";
    }

    public function execute(RestoreContext $context): void
    {
        $this->driver->extractArchive($context->archive, $context->destination);
    }
}