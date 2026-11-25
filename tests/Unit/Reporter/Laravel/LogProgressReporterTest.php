<?php

namespace Tests\Unit\Reporter\Laravel;

use Deagel1337\Backup\Kit\Reporter\Laravel\LogProgressReporter;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LogProgressReporterTest extends TestCase
{
    public function test_reports_progress_using_structured_log_context(): void
    {
        $records = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))
            ->method('info')
            ->willReturnCallback(static function ($message, array $context = []) use (&$records): void {
                $records[] = [(string) $message, $context];
            });

        $reporter = new LogProgressReporter($logger);

        $reporter->started(2);
        $reporter->stepStarted(1, 2, 'Backup');
        $reporter->stepFinished(1, 2, 'Backup');
        $reporter->finished();

        self::assertSame([
            ['Backup-kit progress started.', ['total_steps' => 2]],
            ['Backup-kit step started.', ['step' => 1, 'total_steps' => 2, 'name' => 'Backup']],
            ['Backup-kit step finished.', ['step' => 1, 'total_steps' => 2, 'name' => 'Backup']],
            ['Backup-kit progress finished.', []],
        ], $records);
    }

    public function test_logs_failure_type_without_exception_message(): void
    {
        $exception = new Exception('Sensitive connection details');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Backup-kit step failed.',
                [
                    'step' => 2,
                    'total_steps' => 2,
                    'name' => 'Restore',
                    'exception_class' => Exception::class,
                ],
            );

        (new LogProgressReporter($logger))->stepFailed(2, 2, 'Restore', $exception);
    }
}
