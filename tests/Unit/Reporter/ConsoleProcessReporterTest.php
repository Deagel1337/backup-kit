<?php

declare(strict_types=1);

namespace Tests\Unit\Process;

use Deagel1337\Backup\Kit\Reporter\ConsoleProcessReporter;
use PHPUnit\Framework\TestCase;

final class ConsoleProcessReporterTest extends TestCase
{
    public function test_it_reports_command(): void
    {
        $reporter = new ConsoleProcessReporter();

        $reporter->command([
            'mariadb-dump',
            '--host=127.0.0.1',
            '--port=3307',
            'backup_source',
        ]);

        self::expectOutputString(
            PHP_EOL .
            "Would execute:" . PHP_EOL .
            " 'mariadb-dump' '--host=127.0.0.1' '--port=3307' 'backup_source'" .
            PHP_EOL
        );
    }

    public function test_it_quotes_command_arguments(): void
    {
        $reporter = new ConsoleProcessReporter();

        $reporter->command([
            'borg',
            'create',
            '/path with spaces/repository',
            'archive-name',
        ]);

        self::expectOutputString(
            PHP_EOL .
            "Would execute:" . PHP_EOL .
            " 'borg' 'create' '/path with spaces/repository' 'archive-name'" .
            PHP_EOL
        );
    }

    public function test_it_reports_empty_command(): void
    {
        $reporter = new ConsoleProcessReporter();

        $reporter->command([]);

        self::expectOutputString(
            PHP_EOL .
            "Would execute:" . PHP_EOL .
            " " .
            PHP_EOL
        );
    }
}