<?php

namespace Backup\Php\Process\Interface;

use Backup\Php\Process\Model\ProcessResult;

interface ProcessRunner
{
    public function run(
        array $command, 
        array $environment = [], 
        ?string $workingDirectory = null,
        ?string $outputFile = null,
        ?string $inputFile = null
    ): ProcessResult;
}