<?php

namespace Deagel1337\Backup\Kit\Services;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Services\Interface\RestoreServiceInterface;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\RestoreRollbackHandler;
use Deagel1337\Backup\Kit\Step\Restore\CleanRollbackDump;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use RuntimeException;
use Throwable;

final class RestoreService implements RestoreServiceInterface
{
    /**
     * Orchestriert Restore- und optional Cleanup-Schritte.
     *
     * @param  array<RestoreStep>  $steps  In Ausführungsreihenfolge konfigurierte Schritte.
     * @param  array<RestoreStep>  $cleanupSteps  Auch bei Fehlern in Reihenfolge ausgeführte Cleanup-Schritte.
     */
    public function __construct(
        private readonly array $steps,
        private readonly StepRunner $runner,
        private readonly ?RestoreRollbackHandler $rollback = null,
        private readonly array $cleanupSteps = [],
    ) {}

    /**
     * @see RestoreServiceInterface::restore()
     */
    public function restore(RestoreContext $context): void
    {
        $failure = null;
        $rollbackFailed = false;

        try {
            $this->runner->run(
                $this->steps,
                $context,
                static fn (RestoreStep $step, RestoreContext $context) => $step->execute($context),
            );
        } catch (Throwable $e) {
            $failure = $e;

            if ($this->rollback !== null && $context->databaseRestoreStarted && ! $context->archiveRestoreCommitted) {
                try {
                    $this->rollback->rollback($context);
                } catch (Throwable $rollbackFailure) {
                    $failure = new RuntimeException(
                        'Die Wiederherstellung ist fehlgeschlagen und das Rollback ebenfalls: '.$rollbackFailure->getMessage(),
                        previous: $e,
                    );
                    $rollbackFailed = true;
                }
            }
        }

        $cleanupFailures = [];
        $cleanupSteps = $rollbackFailed
            ? array_values(array_filter(
                $this->cleanupSteps,
                static fn (RestoreStep $step): bool => ! $step instanceof CleanRollbackDump,
            ))
            : $this->cleanupSteps;

        if ($cleanupSteps !== []) {
            try {
                $this->runner->run(
                    $cleanupSteps,
                    $context,
                    static fn (RestoreStep $step, RestoreContext $context) => $step->execute($context),
                    continueOnFailure: true,
                );
            } catch (Throwable $cleanupFailure) {
                $cleanupFailures[] = $cleanupFailure;
            }
        }

        if ($failure !== null) {
            if ($cleanupFailures !== []) {
                $messages = array_map(
                    static fn (Throwable $cleanupFailure): string => $cleanupFailure->getMessage(),
                    $cleanupFailures,
                );

                throw new RuntimeException(
                    $failure->getMessage().' Zusätzliche Fehler beim Aufräumen: '.implode('; ', $messages),
                    previous: $failure,
                );
            }

            throw $failure;
        }

        if ($cleanupFailures !== []) {
            $messages = array_map(
                static fn (Throwable $cleanupFailure): string => $cleanupFailure->getMessage(),
                $cleanupFailures,
            );

            throw new RuntimeException(
                'Die Wiederherstellung wurde ausgeführt, aber das Aufräumen ist fehlgeschlagen: '
                .implode('; ', $messages),
                previous: $cleanupFailures[0],
            );
        }
    }
}
