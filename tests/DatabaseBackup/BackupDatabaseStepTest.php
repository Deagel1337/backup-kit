<?php

namespace Tests\Step;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Step\BackupDatabaseStep;
use RuntimeException;

final class BackupDatabaseStepTest extends TestCase
{
    public function testCreatesAndValidatesDump(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $dump = new DatabaseDump('/tmp/path','','sql');

        $driver
            ->expects($this->once())
            ->method('createDump')
            ->with('/tmp/backup')
            ->willReturn($dump);

        $step = new BackupDatabaseStep($driver);

        $result = $step->execute('/tmp/backup');

        $this->assertSame($dump, $result);
    }

    public function testCreateDumpExceptionIsPropagated(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $driver
            ->expects($this->once())
            ->method('createDump')
            ->willThrowException(
                new RuntimeException('Backup failed')
            );

        $step = new BackupDatabaseStep($driver);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup failed');

        $step->execute('/tmp/backup');
    }
}