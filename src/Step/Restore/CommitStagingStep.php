<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Override;
use RuntimeException;

final class CommitStagingStep implements RestoreStep
{
    #[Override]
    public function name(): string
    {
        return 'Staging-Verzeichnis übernehmen';
    }

    #[Override]
    public function execute(RestoreContext $context): void
    {
        if ($context->archive === null) {
            return;
        }

        $stagingDestination = $context->stagingDestination;
        if (! $context->stagingCreated
            || $stagingDestination === null
            || ! is_dir($stagingDestination)
            || is_link($stagingDestination)) {
            throw new RuntimeException('Ein angelegtes Staging-Verzeichnis ist für den Commit erforderlich.');
        }

        if ($context->destination === '' || is_link($context->destination)) {
            throw new RuntimeException('Das Restore-Ziel ist ungültig oder ein symbolischer Link.');
        }

        $destinationParent = realpath(dirname($context->destination));
        $stagingParent = realpath(dirname($stagingDestination));
        if ($destinationParent === false || $destinationParent !== $stagingParent) {
            throw new RuntimeException('Staging-Verzeichnis und Restore-Ziel müssen im selben Elternverzeichnis liegen.');
        }

        $destinationName = basename(rtrim($context->destination, DIRECTORY_SEPARATOR));
        $stagingName = basename(rtrim($stagingDestination, DIRECTORY_SEPARATOR));
        if ($destinationName === '' || $destinationName === '.' || $destinationName === '..'
            || $stagingName === '' || $stagingName === '.' || $stagingName === '..') {
            throw new RuntimeException('Ungültiger Pfad für den Staging-Commit.');
        }

        $destination = $destinationParent.DIRECTORY_SEPARATOR.$destinationName;
        $stagingDestination = $stagingParent.DIRECTORY_SEPARATOR.$stagingName;
        if ($destination === $stagingDestination) {
            throw new RuntimeException('Staging-Verzeichnis und Restore-Ziel dürfen nicht identisch sein.');
        }

        if (is_link($destination) || (file_exists($destination) && ! is_dir($destination))) {
            throw new RuntimeException('Das vorhandene Restore-Ziel ist kein Verzeichnis.');
        }

        $backupDestination = null;
        if (file_exists($destination)) {
            $backupDestination = $this->uniqueBackupPath($destinationParent, $destinationName);
            if (! @rename($destination, $backupDestination)) {
                throw new RuntimeException('Das vorhandene Restore-Ziel konnte nicht gesichert werden.');
            }
        }

        if (! @rename($stagingDestination, $destination)) {
            if ($backupDestination !== null && ! @rename($backupDestination, $destination)) {
                throw new RuntimeException(
                    'Das Staging-Verzeichnis konnte nicht übernommen werden. '
                    .'Das alte Restore-Ziel ist unter '.$backupDestination.' erhalten.'
                );
            }

            throw new RuntimeException('Das Staging-Verzeichnis konnte nicht übernommen werden.');
        }

        $context->stagingCreated = false;
        $context->stagingDestination = null;
        $context->stagingDestinationProvided = false;
        $context->archiveRestoreCommitted = true;

        if ($backupDestination !== null) {
            try {
                RestoreDirectoryCleaner::remove($backupDestination);
            } catch (\Throwable $exception) {
                throw new RuntimeException(
                    'Das Archiv wurde übernommen, aber die alte Sicherung konnte nicht entfernt werden: '
                    .$backupDestination,
                    previous: $exception,
                );
            }
        }
    }

    private function uniqueBackupPath(string $parent, string $destinationName): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $parent.DIRECTORY_SEPARATOR
                .'.'.$destinationName.'.restore-backup-'.bin2hex(random_bytes(8));

            if (! file_exists($candidate) && ! is_link($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Es konnte kein eindeutiger Sicherungspfad erstellt werden.');
    }
}
