<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class CheckBackupExistsStep implements RestoreStep
{
    public function __construct(private readonly ArchiveDriver $archive) {}

    public function name(): string
    {
        return 'Überprüf, ob das Backup vorhanden ist.';
    }

    public function execute(RestoreContext $context): void
    {
        if(!$context->archive) {
            throw new RuntimeException("No Archive Found");
        }
        
        $this->archive->listArchive($context->archive);
    }
}