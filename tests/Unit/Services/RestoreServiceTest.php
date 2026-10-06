<?php

namespace Tests\Unit\Services;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Services\RestoreService;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDatabaseStep;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\RestoreRollbackHandler;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestoreServiceTest extends TestCase
{
    /**
     * Creates a mock service
     *
     * @param  array<RestoreStep>  $steps
     * @return array{
     *   progress: MockObject,
     *   rollback: MockObject,
     *   service: RestoreService
     * }
     */
    private function createRestoreService(array $steps): array
    {
        $progress = $this->createMock(ProgressReporter::class);
        $runner = new StepRunner($progress);
        $rollback = $this->createMock(RestoreRollbackHandler::class);

        return [
            'progress' => $progress,
            'rollback' => $rollback,
            'service' => new RestoreService(
                steps: $steps,
                runner: $runner,
                rollback: $rollback),
        ];
    }

    public function test_runs_all_steps_in_order(): void
    {
        $step1 = $this->createMock(RestoreStep::class);
        $step2 = $this->createMock(RestoreStep::class);

        $setup = $this->createRestoreService([$step1, $step2]);
        $progress = $setup['progress'];
        $setup['rollback']->expects($this->never())->method('rollback');
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

    public function test_rolls_back_and_removes_snapshot_after_database_restore_failure(): void
    {
        $snapshotPath = tempnam(sys_get_temp_dir(), 'restore_rollback_');
        $this->assertNotFalse($snapshotPath);
        $context = new RestoreContext(
            new ArchiveInfo('/tmp/backup.tar.gz', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump.sql', 'mariadb', 'sql'),
            '/tmp/restore',
            rollbackDump: new DatabaseDump($snapshotPath, 'mariadb', 'sql'),
        );
        $database = $this->createMock(DatabaseBackupDriver::class);
        $database
            ->expects($this->once())
            ->method('restoreDump')
            ->willThrowException(new RuntimeException('database restore failed'));
        $setup = $this->createRestoreService([new RestoreDatabaseStep($database)]);
        $setup['rollback']
            ->expects($this->once())
            ->method('rollback')
            ->with($context);

        try {
            $setup['service']->restore($context);
            $this->fail('Expected restore failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('database restore failed', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($snapshotPath);
    }

    public function test_starts_progress_with_correct_number_of_steps(): void
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

    public function test_reports_step_number_total_and_name(): void
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

    public function test_stops_when_step_fails(): void
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

    public function test_can_run_without_steps(): void
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
