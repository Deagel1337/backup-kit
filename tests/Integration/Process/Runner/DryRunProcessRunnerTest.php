<?php

declare(strict_types=1);

namespace Tests\Integration\Process\Runner;

use Backup\Php\Process\Runner\DryRunProcessRunner;
use Backup\Php\Reporter\ConsoleProcessReporter;
use PHPUnit\Framework\TestCase;

final class DryRunProcessRunnerTest extends TestCase
{
    public function testItReportsCommandWithoutExecutingIt(): void
    {
        $reporter = new ConsoleProcessReporter();

        $runner = new DryRunProcessRunner($reporter);

        $runner->run([
            'borg',
            'create',
            '/backup/repository',
            'test-backup',
            '/var/www/html',
        ]);

        self::expectOutputString(
            PHP_EOL .
            "Would execute:" . PHP_EOL .
            " 'borg' 'create' '/backup/repository' 'test-backup' '/var/www/html'" .
            PHP_EOL
        );
    }

    public function testItDoesNotCreateOutputFile(): void
    {
        $reporter = new ConsoleProcessReporter();

        $runner = new DryRunProcessRunner($reporter);

        $outputFile = sys_get_temp_dir()
            . '/dry-run-' . bin2hex(random_bytes(8)) . '.sql';

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