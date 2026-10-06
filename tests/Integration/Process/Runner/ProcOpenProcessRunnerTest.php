<?php

declare(strict_types=1);

namespace Tests\Integration\Process\Runner;

use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ProcOpenProcessRunnerTest extends TestCase
{
    private ProcOpenProcessRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runner = new ProcOpenProcessRunner;
    }

    public function test_runs_process_successfully(): void
    {
        $result = $this->runner->run([
            PHP_BINARY,
            '-r',
            'echo "hello";',
        ]);

        self::assertSame(0, $result->exitCode);
        self::assertSame('hello', $result->output);
        self::assertSame('', $result->errorOutput);
    }

    public function test_captures_stderr(): void
    {
        $result = $this->runner->run([
            PHP_BINARY,
            '-r',
            'fwrite(STDERR, "something went wrong");',
        ]);

        self::assertSame(0, $result->exitCode);
        self::assertSame('', $result->output);
        self::assertSame('something went wrong', $result->errorOutput);
    }

    public function test_returns_non_zero_exit_code(): void
    {
        $result = $this->runner->run([
            PHP_BINARY,
            '-r',
            'exit(42);',
        ]);

        self::assertSame(42, $result->exitCode);
    }

    public function test_passes_environment_variables(): void
    {
        $result = $this->runner->run(
            command: [
                PHP_BINARY,
                '-r',
                'echo getenv("BACKUP_TEST_VALUE");',
            ],
            environment: [
                'BACKUP_TEST_VALUE' => 'hello-from-test',
            ],
        );

        self::assertSame(0, $result->exitCode);
        self::assertSame('hello-from-test', $result->output);
    }

    public function test_uses_working_directory(): void
    {
        $workingDirectory = sys_get_temp_dir();

        $result = $this->runner->run(
            command: [
                PHP_BINARY,
                '-r',
                'echo getcwd();',
            ],
            workingDirectory: $workingDirectory,
        );

        self::assertSame(0, $result->exitCode);
        self::assertSame(
            realpath($workingDirectory),
            realpath($result->output),
        );
    }

    public function test_writes_stdout_to_output_file(): void
    {
        $outputFile = tempnam(
            sys_get_temp_dir(),
            'process-runner-',
        );

        self::assertIsString($outputFile);

        try {
            $result = $this->runner->run(
                command: [
                    PHP_BINARY,
                    '-r',
                    'echo "output-to-file";',
                ],
                outputFile: $outputFile,
            );

            self::assertSame(0, $result->exitCode);
            self::assertSame('', $result->output);
            self::assertSame(
                'output-to-file',
                file_get_contents($outputFile),
            );
        } finally {
            @unlink($outputFile);
        }
    }

    public function test_reads_stdin_from_input_file(): void
    {
        $inputFile = tempnam(
            sys_get_temp_dir(),
            'process-runner-input-',
        );

        self::assertIsString($inputFile);

        try {
            file_put_contents(
                $inputFile,
                'input-from-file',
            );

            $result = $this->runner->run(
                command: [
                    PHP_BINARY,
                    '-r',
                    'echo file_get_contents("php://stdin");',
                ],
                inputFile: $inputFile,
            );

            self::assertSame(0, $result->exitCode);
            self::assertSame('input-from-file', $result->output);
            self::assertSame('', $result->errorOutput);
        } finally {
            @unlink($inputFile);
        }
    }

    public function test_throws_exception_when_process_cannot_be_started(): void
    {
        $this->expectException(RuntimeException::class);

        $this->runner->run([
            '/this/command/does/not/exist',
        ]);
    }
}
