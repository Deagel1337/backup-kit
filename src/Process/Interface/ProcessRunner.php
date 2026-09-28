<?php

namespace Deagel1337\Backup\Kit\Process\Interface;

use Deagel1337\Backup\Kit\Process\Model\ProcessResult;

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