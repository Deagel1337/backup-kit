<?php

namespace Tests\Unit\Services;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Services\BackupService;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupServiceTest extends TestCase
{
    public function test_runs_all_steps_in_order(): void
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);
        $step1 = $this->createMock(BackupStep::class);
        $step2 = $this->createMock(BackupStep::class);

        $context = new BackupContext('/tmp/backup');

        $order = [];

        $step1
            ->expects($this->once())
            ->method('name')
            ->willReturn('Datenbank sichern');

        $step2
            ->expects($this->once())
            ->method('name')
            ->willReturn('Archiv erstellen');

        $step1
            ->expects($this->once())
            ->method('execute')
            ->with($context)
            ->willReturnCallback(function () use (&$order): void {
                $order[] = 'step1';
            });

        $step2
            ->expects($this->once())
            ->method('execute')
            ->with($context)
            ->willReturnCallback(function () use (&$order): void {
                $order[] = 'step2';
            });

        $progress
            ->expects($this->once())
            ->method('started')
            ->with(2);

        $progress
            ->expects($this->exactly(2))
            ->method('stepStarted')
            ->willReturnCallback(
                function (
                    int $number,
                    int $total,
                    string $name
                ) use (&$order): void {
                    $order[] = 'started:'.$number.':'.$name;
                }
            );

        $progress
            ->expects($this->exactly(2))
            ->method('stepFinished')
            ->willReturnCallback(
                function (
                    int $number,
                    int $total,
                    string $name
                ) use (&$order): void {
                    $order[] = 'finished:'.$number.':'.$name;
                }
            );

        $progress
            ->expects($this->once())
            ->method('finished');

        $service = new BackupService(
            [$step1, $step2],
            $runner
        );

        $service->backup($context);

        $this->assertSame(
            [
                'started:1:Datenbank sichern',
                'step1',
                'finished:1:Datenbank sichern',
                'started:2:Archiv erstellen',
                'step2',
                'finished:2:Archiv erstellen',
            ],
            $order
        );
    }

    public function test_starts_progress_with_correct_number_of_steps(): void
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);
        $step1 = $this->createMock(BackupStep::class);
        $step2 = $this->createMock(BackupStep::class);
        $step3 = $this->createMock(BackupStep::class);

        $step1->method('name')->willReturn('Step 1');
        $step2->method('name')->willReturn('Step 2');
        $step3->method('name')->willReturn('Step 3');

        $step1->expects($this->once())->method('execute');
        $step2->expects($this->once())->method('execute');
        $step3->expects($this->once())->method('execute');

        $progress
            ->expects($this->once())
            ->method('started')
            ->with(3);

        $progress
            ->method('stepStarted');

        $progress
            ->method('stepFinished');

        $progress
            ->expects($this->once())
            ->method('finished');

        $service = new BackupService(
            [$step1, $step2, $step3],
            $runner
        );

        $service->backup(
            new BackupContext('/tmp/backup')
        );
    }

    public function test_reports_step_number_total_and_name(): void
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);
        $step = $this->createMock(BackupStep::class);

        $context = new BackupContext('/tmp/backup');

        $step
            ->expects($this->once())
            ->method('name')
            ->willReturn('Datenbank sichern');

        $step
            ->expects($this->once())
            ->method('execute')
            ->with($context);

        $progress
            ->expects($this->once())
            ->method('started')
            ->with(1);

        $progress
            ->expects($this->once())
            ->method('stepStarted')
            ->with(
                1,
                1,
                'Datenbank sichern'
            );

        $progress
            ->expects($this->once())
            ->method('stepFinished')
            ->with(
                1,
                1,
                'Datenbank sichern'
            );

        $progress
            ->expects($this->once())
            ->method('finished');

        $service = new BackupService(
            [$step],
            $runner,
        );

        $service->backup($context);
    }

    public function test_stops_when_step_fails(): void
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);
        $step1 = $this->createMock(BackupStep::class);
        $step2 = $this->createMock(BackupStep::class);

        $step1
            ->expects($this->once())
            ->method('name')
            ->willReturn('Datenbank sichern');

        $step1
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(
                new RuntimeException('Backup failed')
            );

        // Dieser Step darf überhaupt nicht erreicht werden.
        $step2
            ->expects($this->never())
            ->method('name');

        $step2
            ->expects($this->never())
            ->method('execute');

        $progress
            ->expects($this->once())
            ->method('started')
            ->with(2);

        $progress
            ->expects($this->once())
            ->method('stepStarted')
            ->with(
                1,
                2,
                'Datenbank sichern'
            );

        $progress
            ->expects($this->never())
            ->method('stepFinished');

        $progress
            ->expects($this->never())
            ->method('finished');

        $service = new BackupService(
            [$step1, $step2],
            $runner,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup failed');

        $service->backup(
            new BackupContext('/tmp/backup')
        );
    }

    public function test_can_run_without_steps(): void
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);

        $progress
            ->expects($this->once())
            ->method('started')
            ->with(0);

        $progress
            ->expects($this->never())
            ->method('stepStarted');

        $progress
            ->expects($this->never())
            ->method('stepFinished');

        $progress
            ->expects($this->once())
            ->method('finished');

        $service = new BackupService(
            [],
            $runner
        );

        $service->backup(
            new BackupContext('/tmp/backup')
        );
    }
}
