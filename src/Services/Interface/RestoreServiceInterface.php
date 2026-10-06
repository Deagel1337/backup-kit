<?php

namespace Deagel1337\Backup\Kit\Services\Interface;

use Deagel1337\Backup\Kit\Context\RestoreContext;

interface RestoreServiceInterface
{
    /**
     * Führt den Restore aus. Fehlschläge nach Beginn des Datenbank-Restores lösen einen Rollback-Versuch aus.
     * Ein vorhandener Rollback-Dump wird nach dem Ablauf entfernt. Ein Cleanup-Fehler überschreibt keinen
     * bereits aufgetretenen Restore- oder Rollback-Fehler.
     *
     * @throws \Throwable Wenn ein Restore-Schritt, der Rollback oder der Snapshot-Cleanup fehlschlägt.
     */
    public function restore(RestoreContext $context): void;
}
