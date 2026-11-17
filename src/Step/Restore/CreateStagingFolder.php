<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Override;
use RuntimeException;

final class CreateStagingFolder implements RestoreStep
{
    #[Override]
    public function name(): string
    {
        return 'Staging-Verzeichnis erstellen';
    }

    #[Override]
    public function execute(RestoreContext $context): void
    {
        if ($context->archive === null) {
            return;
        }

        if ($context->stagingCreated) {
            throw new RuntimeException('Das Staging-Verzeichnis wurde bereits erstellt.');
        }

        $stagingDestinationProvided = $context->stagingDestination !== null;

        if ($context->destination === '') {
            throw new RuntimeException('Kein Zielverzeichnis für die Wiederherstellung angegeben.');
        }

        $destinationParent = realpath(dirname($context->destination));
        if ($destinationParent === false) {
            throw new RuntimeException('Das Elternverzeichnis des Restore-Ziels ist nicht verfügbar.');
        }

        $destinationName = basename(rtrim($context->destination, DIRECTORY_SEPARATOR));
        if ($destinationName === '' || $destinationName === '.' || $destinationName === '..') {
            throw new RuntimeException('Ungültiges Zielverzeichnis für die Wiederherstellung.');
        }

        $destination = $destinationParent.DIRECTORY_SEPARATOR.$destinationName;
        if (is_link($destination) || (file_exists($destination) && ! is_dir($destination))) {
            throw new RuntimeException('Das Ziel für die Wiederherstellung ist kein sicheres Verzeichnis.');
        }

        if ($context->stagingDestination !== null) {
            $stagingParent = realpath(dirname($context->stagingDestination));
            $stagingName = basename(rtrim($context->stagingDestination, DIRECTORY_SEPARATOR));

            if ($stagingParent === false || $stagingParent !== $destinationParent) {
                throw new RuntimeException('Das Staging-Verzeichnis muss neben dem Restore-Ziel liegen.');
            }

            if ($stagingName === '' || $stagingName === '.' || $stagingName === '..') {
                throw new RuntimeException('Ungültiges Staging-Verzeichnis.');
            }

            $stagingDestination = $stagingParent.DIRECTORY_SEPARATOR.$stagingName;
            if ($stagingDestination === $destination) {
                throw new RuntimeException('Staging-Verzeichnis und Restore-Ziel dürfen nicht identisch sein.');
            }

            if (! @mkdir($stagingDestination, 0700)) {
                throw new RuntimeException('Das Staging-Verzeichnis für die Wiederherstellung konnte nicht erstellt werden.');
            }
        } else {
            $stagingDestination = '';
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = $destinationParent.DIRECTORY_SEPARATOR
                    .'.'.$destinationName.'.restore-'.bin2hex(random_bytes(8));

                if (@mkdir($candidate, 0700)) {
                    $stagingDestination = $candidate;
                    break;
                }

                if (! file_exists($candidate) && ! is_link($candidate)) {
                    throw new RuntimeException('Das Staging-Verzeichnis für die Wiederherstellung konnte nicht erstellt werden.');
                }
            }

            if ($stagingDestination === '') {
                throw new RuntimeException('Es konnte kein eindeutiges Staging-Verzeichnis erstellt werden.');
            }
        }

        $context->stagingDestination = $stagingDestination;
        $context->stagingCreated = true;
        $context->stagingDestinationProvided = $stagingDestinationProvided;
    }
}
