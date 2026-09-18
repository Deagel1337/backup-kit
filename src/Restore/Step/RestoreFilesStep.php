<?php

namespace Restore\Step;

use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;

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