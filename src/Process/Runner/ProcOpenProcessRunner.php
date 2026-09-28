<?php

namespace Deagel1337\Backup\Kit\Process\Runner;


use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use RuntimeException;

final class ProcOpenProcessRunner implements ProcessRunner
{
    public function run(
        array $command,
        array $environment = [],
        ?string $workingDirectory = null,
        ?string $outputFile = null,
        ?string $inputFile = null
    ): ProcessResult 
    {
        $stdout = $outputFile !== null
            ? ['file', $outputFile, 'w']
            : ['pipe', 'w'];

        $stdin = $inputFile !== null
            ? ['file', $inputFile, 'r']
            : ['pipe', 'r'];

        $process = proc_open(
            $command,
            [
                0 => $stdin,
                1 => $stdout,
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $workingDirectory,
            $environment
        );

        if (!is_resource($process)) {
            throw new RuntimeException(
                'Der Prozess konnte nicht gestartet werden.'
            );
        }

        try {
            if($inputFile == null) {
                fclose($pipes[0]);
            }

            $output = '';

            if ($outputFile === null) {
                $output = stream_get_contents($pipes[1]);

                if ($output === false) {
                    throw new RuntimeException(
                        'Der Prozess-Output konnte nicht gelesen werden.'
                    );
                }
            }

            $errorOutput = stream_get_contents($pipes[2]);

            if ($errorOutput === false) {
                throw new RuntimeException(
                    'Der Fehler-Output konnte nicht gelesen werden.'
                );
            }

            if($outputFile == null) {
                fclose($pipes[1]);
            }
            
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            return new ProcessResult(
                $exitCode,
                $output,
                $errorOutput
            );
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }
    }
}