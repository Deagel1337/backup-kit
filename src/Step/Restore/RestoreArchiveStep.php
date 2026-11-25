<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use RuntimeException;

final class RestoreArchiveStep implements RestoreStep
{
    /**
     * @param  string|null  $destination  Optionales Ziel für die direkte Extraktion.
     */
    public function __construct(
        private readonly ArchiveDriver $driver,
        private readonly ?string $destination = null,
    ) {}

    public function name(): string
    {
        return 'Archiv wiederherstellen';
    }

    /** @throws RuntimeException Wenn Archiv oder Ziel fehlen oder das Ziel nicht nutzbar ist. */
    public function execute(RestoreContext $context): void
    {
        if ($context->archive === null) {
            throw new RuntimeException('No Archive available');
        }

        if ($context->stagingCreated || $context->stagingDestination !== null) {
            if ($this->destination !== null && $this->destination !== $context->destination) {
                throw new RuntimeException('Das Restore-Ziel muss vor der Validierung im Context festgelegt werden.');
            }

            $destination = $context->stagingDestination;
            if (! $context->stagingCreated || $destination === null || ! is_dir($destination) || is_link($destination)) {
                throw new RuntimeException('Das Staging-Verzeichnis muss vor der Extraktion erstellt werden.');
            }

            $destinationParent = realpath(dirname($context->destination));
            if ($destinationParent === false || $destinationParent !== realpath(dirname($destination))) {
                throw new RuntimeException('Das Staging-Verzeichnis muss neben dem Restore-Ziel liegen.');
            }
        } else {
            if ($this->destination !== null && $this->destination !== $context->destination) {
                throw new RuntimeException('Das Restore-Ziel muss vor der Validierung im Context festgelegt werden.');
            }

            $destination = $context->destination;
            if ($destination === '' || is_link(rtrim($destination, DIRECTORY_SEPARATOR))) {
                throw new RuntimeException('Das Restore-Ziel ist ungültig oder ein symbolischer Link.');
            }
            if (! is_dir($destination) && ! @mkdir($destination, 0700, true) && ! is_dir($destination)) {
                throw new RuntimeException('Das Zielverzeichnis für die Wiederherstellung konnte nicht erstellt werden.');
            }
        }

        $this->driver->extractArchive($context->archive, $destination);
        if (! $context->stagingCreated) {
            $context->archiveRestoreCommitted = true;
        }
    }
}
