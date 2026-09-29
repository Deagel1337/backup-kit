<?php

namespace Deagel1337\Backup\Kit\Archive\Model;

final readonly class ArchiveEntry
{
    public function __construct(
        public string $path,
        public int $size,
        public ArchiveEntryType $type
    ) {}

    public function extension(): ?string
    {
        $extention = pathinfo($this->path, PATHINFO_EXTENSION);

        return $extention !== ''
            ? $extention
            : null;
    }

    public function filename(): string
    {
        return basename($this->path);
    }
}