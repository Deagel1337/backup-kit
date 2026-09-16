<?php

namespace Process\ProcessRunner;

use Process\Model\ProcessResult;

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