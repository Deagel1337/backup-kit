<?php

namespace Tests\Step;

use DatabaseBackup\Driver\DatabaseBackupDriver;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Context\BackupContext;
use Restore\Step\BackupDatabaseStep;
use RuntimeException;

final class BackupDatabaseStepTest extends TestCase
{
    public function testCreatesAndValidatesDump(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $dump = new DatabaseDump('/tmp/backup', '', 'sql');

        $driver
            ->expects($this->once())
            ->method('createDump')
            ->with('/tmp/backup')
            ->willReturn($dump);

        $driver
            ->expects($this->once())
            ->method('validateDump')
            ->with($dump);

        $context = new BackupContext('/tmp/backup');

        $step = new BackupDatabaseStep($driver);

        $step->execute($context);

        $this->assertSame($dump, $context->dump);
    }

    public function testHasExpectedName(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $step = new BackupDatabaseStep($driver);

        $this->assertSame(
            'Datenbank sichern',
            $step->name()
        );
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

        $context = new BackupContext('/tmp/backup');

        $step = new BackupDatabaseStep($driver);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup failed');

        $step->execute($context);
    }
}
