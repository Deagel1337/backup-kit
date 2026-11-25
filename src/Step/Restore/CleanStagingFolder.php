<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Override;

final class CleanStagingFolder implements RestoreStep
{
    #[Override]
    public function name(): string
    {
        return 'Staging-Verzeichnis aufräumen';
    }

    public function execute(RestoreContext $context): void
    {
        if (! $context->stagingCreated || $context->stagingDestination === null) {
            return;
        }

        RestoreDirectoryCleaner::remove($context->stagingDestination);
        $context->stagingCreated = false;
        $context->stagingDestination = null;
        $context->stagingDestinationProvided = false;
    }
}
