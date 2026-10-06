<?php

namespace Deagel1337\Backup\Kit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use RuntimeException;

final class VerifyBackupArchiveStep implements BackupStep
{
    public function __construct(private readonly ArchiveService $archives) {}

    public function name(): string
    {
        return 'Backup-Archiv überprüfen';
    }

    public function execute(BackupContext $context): void
    {
        if ($context->archive === null) {
            throw new RuntimeException('Es wurde kein Archiv zum Überprüfen erstellt.');
        }

        $entries = iterator_to_array($this->archives->listArchiveContent($context->archive));
        $entryNames = array_map(
            static fn (ArchiveEntry $entry): string => $entry->filename(),
            $entries,
        );
        $expectedFiles = $context->getFiles();
        if ($context->dump !== null) {
            $expectedFiles[] = $context->dump->path;
        }

        foreach ($expectedFiles as $path) {
            $filename = basename(rtrim($path, DIRECTORY_SEPARATOR));
            if (! in_array($filename, $entryNames, true)) {
                throw new RuntimeException('Die Backup-Quelle fehlt im Archiv: '.$path);
            }
        }
    }
}
