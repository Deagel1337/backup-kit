<?php

namespace Tests\Restore\Step;

use Archive\Model\ArchiveInfo;
use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Model\RestoreContext;
use Restore\Step\RestoreDatabaseStep;
use RuntimeException;

final class RestoreDatabaseStepTest extends TestCase
{
    public function testRestoresContextDump(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $dump = new DatabaseDump(
            '/tmp/dump.sql',
            'postgres',
            'sql'
        );

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            $dump,
            '/tmp/restore'
        );

        $driver
            ->expects($this->once())
            ->method('restoreDump')
            ->with($dump);

        (new RestoreDatabaseStep($driver))->execute($context);
    }

    public function testRestoreExceptionIsPropagated(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $driver
            ->expects($this->once())
            ->method('restoreDump')
            ->with($this->isInstanceOf(DatabaseDump::class))
            ->willThrowException(
                new RuntimeException('restore failed')
            );

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('restore failed');

        (new RestoreDatabaseStep($driver))->execute($context);
    }

    public function testHasExpectedName(): void
    {
        $step = new RestoreDatabaseStep(
            $this->createMock(DatabaseBackupDriver::class)
        );

        $this->assertSame(
            'Datenbank wiederherstellen',
            $step->name()
        );
    }
}