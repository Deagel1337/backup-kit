<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDatabaseStep;
use PHPUnit\Framework\TestCase;
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