<?php

namespace Deagel1337\Backup\Kit\Process\Interface;

use Deagel1337\Backup\Kit\Process\Model\ProcessResult;

interface ProcessRunner
{
    /**
     * Summary of run
     * @param array<string> $command
     * @param array<string> $environment
     * @param string | null $workingDirectory
     * @param string | null $outputFile
     * @param string | null $inputFile
     * @return ProcessResult
     */
    public function run(
        array $command, 
        array $environment = [], 
        ?string $workingDirectory = null,
        ?string $outputFile = null,
        ?string $inputFile = null
    ): ProcessResult;
}