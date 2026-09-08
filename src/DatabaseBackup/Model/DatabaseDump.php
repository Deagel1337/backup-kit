<?php

namespace DatabaseBackup\Model\DatabaseDump;

use RuntimeException;

// Ein Datenmodell, der Informationen über das Backup haben soll
final readonly class DatabaseDump
{
    public function __construct(
        public string $path,
        public string $driver,
        public string $format, 
    ) {
    }

    // Das File-System soll die Größe ermitteln
    public function size(): int
    {
        $size = filesize($this->path);

        if($size == false) {
            throw new RuntimeException(
                "Die Größe des Dumps konnte nicht ermittelt werden: {$this->path}"
            );
        }

        return $size;
    }

    // Das File-System soll ermitteln, ob die Datei existiert
    public function exists(): bool
    {
        return is_file($this->path);
    }

    // Das File-System soll ermitteln, ob die Datei schreibbar ist
    public function isReadable(): bool
    {
        return is_readable($this->path);
    }
}