<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class RestoreFilesStep implements RestoreStep
{
    public function name(): string
    {
        return 'Dateien in die Anwendung kopieren';
    }

    public function execute(RestoreContext $context): void
    {
        
    }
}