<?php

namespace Backup\Php\Process\Runner;

use Backup\Php\Process\Interface\ProcessRunner;
use Backup\Php\Process\Model\ProcessResult;
use Backup\Php\Reporter\Interface\ProcessReporter;
final class DryRunProcessRunner implements ProcessRunner
{
    public function __construct(
        private readonly ProcessReporter $reporter,
    ) {}

    public function run(
        array $command,
        array $environment = [],
        ?string $workingDirectory = null,
        ?string $outputFile = null,
        ?string $inputFile = null
    ): ProcessResult
    {
        $this->reporter->command($command);

        return new ProcessResult(
            exitCode: 0,
            output: '',
            errorOutput: ''
        );
    }
}