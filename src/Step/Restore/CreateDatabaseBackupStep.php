<?php

namespace Backup\Php\Step\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\RestoreStep;

final class CreateDatabaseBackupStep implements RestoreStep
{
    public function name(): string
    {
        return 'Erstellt ein sicherheits Dump der Datenbank, bevor der Restore-Prozess losgeht.';
    }

    public function execute(RestoreContext $context): void
    {

    }
}