<?php

namespace Backup\Php\DatabaseBackup\Model;

use Backup\Php\Exception\DumpDriverException\DumpNotFoundException;
use Backup\Php\Exception\DumpDriverException\DumpNotReadableException;
use Backup\Php\Exception\DumpDriverException\EmptyDumpException;

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
            throw new EmptyDumpException();
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

    public function validate(): void
    {
        if (!$this->exists()) {
            throw new DumpNotFoundException("Dump doesn't exist");
        }

        if (!$this->isReadable()) {
            throw new DumpNotReadableException();
        }

        if ($this->size() <= 0) {
            throw new EmptyDumpException();
        }
    }
}