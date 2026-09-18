<?php

namespace Tests\Services;

use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Interfaces\ProgressReporter;
use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;
use Restore\Runner\StepRunner;
use Src\Services\RestoreService;
use RuntimeException;

final class RestoreServiceTest extends TestCase
{
    private function createRestoreService(array $steps): array
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);

        return [
            'progress' => $progress,
            'service' => new RestoreService($steps, $runner),
        ];
    }

    public function testRunsAllStepsInOrder(): void
    {
        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);
        
        $setup = $this->createRestoreService([$step1, $step2]);
        $progress = $setup['progress'];
        $service = $setup['service'];

        $context = new RestoreContext(
            new ArchiveInfo(
                '/tmp/backup.tar.gz',
                'tar',
                'tar.gz'
            ),
            new DatabaseDump(
                '/tmp/dump.sql',
                'postgres',
                'sql'
            ),
            '/tmp/restore'
        );

        $order = [];

        $step1
            ->expects($this->once())
            ->method('name')
            ->willReturn('Archiv validieren');

        $step2
            ->expects($this->once())
            ->method('name')
            ->willReturn('Datenbank wiederherstellen');

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
                    $order[] = 'started:' . $number . ':' . $name;
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
                    $order[] = 'finished:' . $number . ':' . $name;
                }
            );

        $progress
            ->expects($this->once())
            ->method('finished');


        $service->restore($context);

        $this->assertSame(
            [
                'started:1:Archiv validieren',
                'step1',
                'finished:1:Archiv validieren',
                'started:2:Datenbank wiederherstellen',
                'step2',
                'finished:2:Datenbank wiederherstellen',
            ],
            $order
        );
    }

    public function testStartsProgressWithCorrectNumberOfSteps(): void
    {
        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);
        $step3 = $this->createMock(RestoreStep::class);
        
        $setup = $this->createRestoreService([$step1, $step2, $step3]);
        $progress = $setup['progress'];
        $service = $setup['service'];

        $step1
            ->method('name')
            ->willReturn('Step 1');

        $step2
            ->method('name')
            ->willReturn('Step 2');

        $step3
            ->method('name')
            ->willReturn('Step 3');

        $step1
            ->expects($this->once())
            ->method('execute');

        $step2
            ->expects($this->once())
            ->method('execute');

        $step3
            ->expects($this->once())
            ->method('execute');

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

        $service->restore(
            new RestoreContext(
                new ArchiveInfo(
                    '/tmp/backup.tar.gz',
                    'tar',
                    'tar.gz'
                ),
                new DatabaseDump(
                    '/tmp/dump.sql',
                    'postgres',
                    'sql'
                ),
                '/tmp/restore'
            )
        );
    }

    public function testReportsStepNumberTotalAndName(): void
    {
        $step = $this->createMock(RestoreStep::class);

        
        $setup = $this->createRestoreService([$step]);
        $progress = $setup['progress'];
        $service = $setup['service'];

        $context = new RestoreContext(
            new ArchiveInfo(
                '/tmp/backup.tar.gz',
                'tar',
                'tar.gz'
            ),
            new DatabaseDump(
                '/tmp/dump.sql',
                'postgres',
                'sql'
            ),
            '/tmp/restore'
        );

        $step
            ->expects($this->once())
            ->method('name')
            ->willReturn('Archiv wiederherstellen');

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
                'Archiv wiederherstellen'
            );

        $progress
            ->expects($this->once())
            ->method('stepFinished')
            ->with(
                1,
                1,
                'Archiv wiederherstellen'
            );

        $progress
            ->expects($this->once())
            ->method('finished');

        $service->restore($context);
    }

    public function testStopsWhenStepFails(): void
    {
        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);

        
        $setup = $this->createRestoreService([$step1, $step2]);
        $progress = $setup['progress'];
        $service = $setup['service'];

        $step1
            ->expects($this->once())
            ->method('name')
            ->willReturn('Archiv validieren');

        $step1
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(
                new RuntimeException('Restore failed')
            );

        // Der zweite Step darf nach dem Fehler
        // überhaupt nicht mehr erreicht werden.
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
                'Archiv validieren'
            );

        $progress
            ->expects($this->never())
            ->method('stepFinished');

        $progress
            ->expects($this->never())
            ->method('finished');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Restore failed');

        $service->restore(
            new RestoreContext(
                new ArchiveInfo(
                    '/tmp/backup.tar.gz',
                    'tar',
                    'tar.gz'
                ),
                new DatabaseDump(
                    '/tmp/dump.sql',
                    'postgres',
                    'sql'
                ),
                '/tmp/restore'
            )
        );
    }

    public function testCanRunWithoutSteps(): void
    {

        $setup = $this->createRestoreService([]);
        $progress = $setup['progress'];
        $service = $setup['service'];
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

        $service->restore(
            new RestoreContext(
                new ArchiveInfo(
                    '/tmp/backup.tar.gz',
                    'tar',
                    'tar.gz'
                ),
                new DatabaseDump(
                    '/tmp/dump.sql',
                    'postgres',
                    'sql'
                ),
                '/tmp/restore'
            )
        );
    }
}