<?php

declare(strict_types=1);

namespace Tests\Unit\Process\Runner;

use Deagel1337\Backup\Kit\Process\Runner\DryRunProcessRunner;
use Deagel1337\Backup\Kit\Reporter\Interface\ProcessReporter;
use PHPUnit\Framework\TestCase;

final class DryRunProcessRunnerTest extends TestCase
{
    public function test_it_reports_command(): void
    {
        $reporter = $this->createMock(ProcessReporter::class);

        $command = [
            'mariadb-dump',
            '--host=127.0.0.1',
            '--port=3307',
            '--user=root',
            'backup_source',
        ];

        $reporter
            ->expects($this->once())
            ->method('command')
            ->with($command);

        $runner = new DryRunProcessRunner($reporter);

        $runner->run($command);
    }

    public function test_it_returns_empty_process_result(): void
    {
        $reporter = $this->createMock(ProcessReporter::class);

        $runner = new DryRunProcessRunner($reporter);

        $result = $runner->run([
            'mariadb-dump',
            'backup_source',
        ]);

        self::assertSame(0, $result->exitCode);
        self::assertSame('', $result->output);
        self::assertSame('', $result->errorOutput);
    }

    public function test_it_does_not_create_output_file(): void
    {
        $reporter = $this->createMock(ProcessReporter::class);

        $runner = new DryRunProcessRunner($reporter);

        $outputFile = sys_get_temp_dir()
            .'/dry-run-'.bin2hex(random_bytes(8)).'.sql';

        $runner->run(
            command: [
                'mariadb-dump',
                'backup_source',
            ],
            outputFile: $outputFile,
        );

        self::assertFileDoesNotExist($outputFile);
    }
}
