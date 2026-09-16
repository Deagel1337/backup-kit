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
        $dump = new DatabaseDump('/tmp/dump.sql', 'postgres', 'sql');
        $context = new RestoreContext(new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'), $dump, '/tmp/restore');

        $driver->expects($this->once())->method('restoreDump')->with($dump);

        (new RestoreDatabaseStep($driver))->execute($context);
    }

    public function testRestoreExceptionIsPropagated(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);
        $driver->method('restoreDump')->willThrowException(new RuntimeException('restore failed'));
        $context = new RestoreContext(new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'), new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'), '/tmp/restore');

        $this->expectExceptionMessage('restore failed');
        (new RestoreDatabaseStep($driver))->execute($context);
    }

    public function testHasExpectedName(): void
    {
        $this->assertSame('Datenbank wiederherstellen', (new RestoreDatabaseStep($this->createMock(DatabaseBackupDriver::class)))->name());
    }
}