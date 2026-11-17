<?php

namespace Deagel1337\Backup\Kit\Step\Restore;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class RestoreDirectoryCleaner
{
    public static function remove(string $directory): void
    {
        if (! file_exists($directory) && ! is_link($directory)) {
            return;
        }

        if (is_link($directory) || ! is_dir($directory)) {
            if (! @unlink($directory)) {
                throw new RuntimeException('Datei oder Link konnte nicht entfernt werden: '.$directory);
            }

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            $path = $entry->getPathname();
            $removed = $entry->isDir() && ! $entry->isLink()
                ? @rmdir($path)
                : @unlink($path);

            if (! $removed) {
                throw new RuntimeException('Ein Eintrag konnte nicht entfernt werden: '.$path);
            }
        }

        if (! @rmdir($directory)) {
            throw new RuntimeException('Verzeichnis konnte nicht entfernt werden: '.$directory);
        }
    }
}
