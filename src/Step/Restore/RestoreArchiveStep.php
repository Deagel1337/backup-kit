<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class RestoreArchiveStep implements RestoreStep
{
    public function __construct(
        private readonly ArchiveDriver $driver,
    ) {}

    public function name(): string
    {
        return 'Archiv wiederherstellen';
    }

    public function execute(RestoreContext $context): void
    {
        if (! $context->archive) {
            throw new RuntimeException('No Archive available');
        }

        $this->driver->extractArchive($context->archive, $context->destination);
    }
}
