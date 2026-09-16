<?php

namespace Tests\Services;

use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Interfaces\ProgressReporter;
use Restore\Interfaces\RestoreStep;
use Restore\Model\RestoreContext;
use Src\Services\RestoreService;
use RuntimeException;

final class RestoreServiceTest extends TestCase
{
    public function testRunsAllStepsInOrder(): void
    {
        $progress = $this->createMock(ProgressReporter::class);

        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);

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

        $service = new RestoreService(
            [$step1, $step2],
            $progress
        );

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
        $progress = $this->createMock(ProgressReporter::class);

        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);
        $step3 = $this->createMock(RestoreStep::class);

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

        $service = new RestoreService(
            [$step1, $step2, $step3],
            $progress
        );

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
        $progress = $this->createMock(ProgressReporter::class);
        $step = $this->createMock(RestoreStep::class);

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

        $service = new RestoreService(
            [$step],
            $progress
        );

        $service->restore($context);
    }

    public function testStopsWhenStepFails(): void
    {
        $progress = $this->createMock(ProgressReporter::class);

        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);

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

        $service = new RestoreService(
            [$step1, $step2],
            $progress
        );

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
        $progress = $this->createMock(ProgressReporter::class);

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

        $service = new RestoreService(
            [],
            $progress
        );

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