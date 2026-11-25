<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class CleanRollbackDump implements RestoreStep
{
    public function name(): string
    {
        return 'Rollback-Dump aufräumen';
    }

    public function execute(RestoreContext $context): void
    {
        if ($context->rollbackDump === null) {
            return;
        }

        $path = $context->rollbackDump->path;
        if ((file_exists($path) || is_link($path))
            && ((! is_file($path) && ! is_link($path)) || ! @unlink($path))) {
            throw new RuntimeException('Der Rollback-Dump konnte nicht entfernt werden: '.$path);
        }

        $context->rollbackDump = null;
    }
}
