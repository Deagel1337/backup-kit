<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Services\Interface\RestoreServiceInterface;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\RestoreRollbackHandler;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use RuntimeException;
use Throwable;

final class RestoreService implements RestoreServiceInterface
{
    /**
     * Orchestriert die Restore-Schritte und verwaltet den Rollback-Dump.
     *
     * @param  array<RestoreStep>  $steps  In Ausführungsreihenfolge konfigurierte Schritte.
     */
    public function __construct(
        private readonly array $steps,
        private readonly StepRunner $runner,
        private readonly RestoreRollbackHandler $rollback,
    ) {}

    /**
     * @see RestoreServiceInterface::restore()
     */
    public function restore(RestoreContext $context): void
    {
        $failure = null;

        try {
            $this->runner->run(
                $this->steps,
                $context,
                static fn (RestoreStep $step, RestoreContext $context) => $step->execute($context),
            );
        } catch (Throwable $e) {
            $failure = $e;

            if ($context->databaseRestoreStarted) {
                try {
                    $this->rollback->rollback($context);
                } catch (Throwable $rollbackFailure) {
                    $failure = new RuntimeException(
                        'Die Wiederherstellung ist fehlgeschlagen und das Rollback ebenfalls: '.$rollbackFailure->getMessage(),
                        previous: $e,
                    );

                    throw $failure;
                }
            }

            throw $e;
        } finally {
            if ($context->rollbackDump !== null
                && is_file($context->rollbackDump->path)
                && ! unlink($context->rollbackDump->path)
                && $failure === null) {
                throw new RuntimeException('Der Rollback-Dump konnte nicht entfernt werden.');
            }
        }
    }
}
