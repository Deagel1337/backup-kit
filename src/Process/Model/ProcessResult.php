<?php

namespace Deagel1337\Backup\Kit\Process\Model;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $output,
        public string $errorOutput
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}
