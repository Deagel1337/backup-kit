<?php

namespace Tests\Unit\Process\Runner;

use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProcOpenProcessRunnerTest extends TestCase
{
    private ProcOpenProcessRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new ProcOpenProcessRunner;
    }

    public function test_runs_command_successfully(): void
    {
        $result = $this->runner->run([
            PHP_BINARY,
            '-r',
            'echo "Hello World";',
        ]);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('Hello World', $result->output);
        $this->assertSame('', $result->errorOutput);
    }

    public function test_returns_error_output_when_command_fails(): void
    {
        $result = $this->runner->run([
            PHP_BINARY,
            '-r',
            'fwrite(STDERR, "Something went wrong"); exit(42);',
        ]);

        $this->assertSame(42, $result->exitCode);
        $this->assertSame('', $result->output);
        $this->assertSame(
            'Something went wrong',
            $result->errorOutput
        );
    }

    public function test_captures_stdout(): void
    {
        $result = $this->runner->run([
            PHP_BINARY,
            '-r',
            'echo "stdout content";',
        ]);

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('stdout content', $result->output);
    }

    public function test_writes_output_to_file(): void
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'process_runner_');

        $this->assertNotFalse($outputFile);

        try {
            $result = $this->runner->run(
                [
                    PHP_BINARY,
                    '-r',
                    'echo "file output";',
                ],
                outputFile: $outputFile
            );

            $this->assertSame(0, $result->exitCode);
            $this->assertSame('', $result->output);
            $this->assertSame('', $result->errorOutput);
            $this->assertSame(
                'file output',
                file_get_contents($outputFile)
            );
        } finally {
            unlink($outputFile);
        }
    }

    public function test_passes_input_file_to_process(): void
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'process_input_');

        $this->assertNotFalse($inputFile);

        file_put_contents($inputFile, 'Hello from file');

        try {
            $result = $this->runner->run(
                [
                    PHP_BINARY,
                    '-r',
                    'echo stream_get_contents(STDIN);',
                ],
                inputFile: $inputFile
            );

            $this->assertSame(0, $result->exitCode);
            $this->assertSame('Hello from file', $result->output);
        } finally {
            unlink($inputFile);
        }
    }

    public function test_uses_working_directory(): void
    {
        $workingDirectory = sys_get_temp_dir();

        $result = $this->runner->run(
            [
                PHP_BINARY,
                '-r',
                'echo getcwd();',
            ],
            workingDirectory: $workingDirectory
        );

        $this->assertSame(0, $result->exitCode);
        $this->assertSame(
            realpath($workingDirectory),
            realpath(trim($result->output))
        );
    }

    public function test_passes_environment_variables(): void
    {
        $result = $this->runner->run(
            [
                PHP_BINARY,
                '-r',
                'echo getenv("MY_TEST_VARIABLE");',
            ],
            environment: [
                'MY_TEST_VARIABLE' => 'test-value',
            ]
        );

        $this->assertSame(0, $result->exitCode);
        $this->assertSame('test-value', $result->output);
    }

    public function test_throws_exception_when_process_cannot_be_started(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Der Prozess konnte nicht gestartet werden.'
        );

        $this->runner->run([
            '/this/command/does/not/exist',
        ]);
    }
}
