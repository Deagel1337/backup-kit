<?php

namespace Deagel1337\Backup\Kit\Process\Runner;

use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use Deagel1337\Backup\Kit\Reporter\Interface\ProcessReporter;
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