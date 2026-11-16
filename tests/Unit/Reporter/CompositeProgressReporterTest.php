<?php

namespace Tests\Unit\Reporter;

use Deagel1337\Backup\Kit\Reporter\CompositeProgressReporter;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Exception;
use PHPUnit\Framework\TestCase;

final class CompositeProgressReporterTest extends TestCase
{
    public function test_forwards_each_progress_callback_to_all_reporters(): void
    {
        $exception = new Exception('Failed');
        $reporters = [
            $this->createMock(ProgressReporter::class),
            $this->createMock(ProgressReporter::class),
        ];

        foreach ($reporters as $reporter) {
            $reporter->expects($this->once())->method('started')->with(2);
            $reporter->expects($this->once())->method('stepStarted')->with(1, 2, 'Backup');
            $reporter->expects($this->once())->method('stepFinished')->with(1, 2, 'Backup');
            $reporter->expects($this->once())->method('stepFailed')->with(2, 2, 'Restore', $exception);
            $reporter->expects($this->once())->method('finished');
        }

        $composite = new CompositeProgressReporter($reporters);

        $composite->started(2);
        $composite->stepStarted(1, 2, 'Backup');
        $composite->stepFinished(1, 2, 'Backup');
        $composite->stepFailed(2, 2, 'Restore', $exception);
        $composite->finished();
    }
}
