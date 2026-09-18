<?php

namespace Restore\Step;

use Restore\Model\RestoreContext;

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