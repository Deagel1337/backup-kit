<?php

namespace Backup\Php\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\BackupStep;

final class ShowBackupContextStep implements BackupStep
{
    public function name(): string
    {
        return 'Backup-Kontext anzeigen';
    }

    public function execute(BackupContext $context): void
    {
        echo "=== Backup Context ===\n";
        echo "Destination: {$context->destination}\n";

        if ($context->dump !== null) {
            echo "Dump:\n";
            echo "  Path: {$context->dump->path}\n";
            echo "  Driver: {$context->dump->driver}\n";
            echo "  Format: {$context->dump->format}\n";
        } else {
            echo "Dump: keiner\n";
        }

        if ($context->archive !== null) {
            echo "Archive:\n";
            echo "  Path: {$context->archive->path}\n";
            echo "  Driver: {$context->archive->driver}\n";
            echo "  Format: {$context->archive->format}\n";
        } else {
            echo "Archive: keines\n";
        }

        echo "=======================\n";
    }
}