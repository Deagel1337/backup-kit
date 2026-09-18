<?php

namespace Backup\Php\Archive\Model;

final readonly class ArchiveInfo
{
    public function __construct(
        public string $path,
        public string $driver,
        public string $format
    ) { }

    public function exists(): bool
    {
        return is_file($this->path) || is_dir($this->path);
    }
}