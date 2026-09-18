<?php

namespace Tests\Unit\Step\Restore;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Step\Restore\ValidateRestoreContextStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;



final class ValidateRestoreContextStepTest extends TestCase
{
    public function testValidatesRequirementsAndContextInOrder(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $dump = new DatabaseDump(
            '/tmp/dump',
            'sqlite',
            'sqlite'
        );

        $archive = new ArchiveInfo(
            '/tmp/archive',
            'tar',
            'tar.gz'
        );

        $context = new RestoreContext(
            $archive,
            $dump,
            '/tmp/restore'
        );

        $step = 0;

        $databaseDriver
            ->expects($this->once())
            ->method('validateRequirements')
            ->willReturnCallback(
                function () use (&$step): void {
                    $this->assertSame(0, $step++);
                }
            );

        $databaseDriver
            ->expects($this->once())
            ->method('validateDump')
            ->with($dump)
            ->willReturnCallback(
                function () use (&$step): void {
                    $this->assertSame(1, $step++);
                }
            );

        $archiveDriver
            ->expects($this->once())
            ->method('validateRequirements')
            ->willReturnCallback(
                function () use (&$step): void {
                    $this->assertSame(2, $step++);
                }
            );

        $archiveDriver
            ->expects($this->once())
            ->method('validateArchive')
            ->with($archive)
            ->willReturnCallback(
                function () use (&$step): void {
                    $this->assertSame(3, $step++);
                }
            );

        (new ValidateRestoreContextStep(
            $databaseDriver,
            $archiveDriver
        ))->execute($context);

        $this->assertSame(4, $step);
    }

    public function testStopsWhenDatabaseRequirementsAreInvalid(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $databaseDriver
            ->expects($this->once())
            ->method('validateRequirements')
            ->willThrowException(
                new RuntimeException('database requirements failed')
            );

        $databaseDriver
            ->expects($this->never())
            ->method('validateDump');

        $archiveDriver
            ->expects($this->never())
            ->method('validateRequirements');

        $archiveDriver
            ->expects($this->never())
            ->method('validateArchive');

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'database requirements failed'
        );

        (new ValidateRestoreContextStep(
            $databaseDriver,
            $archiveDriver
        ))->execute($context);
    }

    public function testStopsWhenDatabaseDumpIsInvalid(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $databaseDriver
            ->expects($this->once())
            ->method('validateRequirements');

        $databaseDriver
            ->expects($this->once())
            ->method('validateDump')
            ->willThrowException(
                new RuntimeException('invalid dump')
            );

        $archiveDriver
            ->expects($this->never())
            ->method('validateRequirements');

        $archiveDriver
            ->expects($this->never())
            ->method('validateArchive');

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid dump');

        (new ValidateRestoreContextStep(
            $databaseDriver,
            $archiveDriver
        ))->execute($context);
    }

    public function testStopsWhenArchiveRequirementsAreInvalid(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $databaseDriver
            ->expects($this->once())
            ->method('validateRequirements');

        $databaseDriver
            ->expects($this->once())
            ->method('validateDump');

        $archiveDriver
            ->expects($this->once())
            ->method('validateRequirements')
            ->willThrowException(
                new RuntimeException('archive requirements failed')
            );

        $archiveDriver
            ->expects($this->never())
            ->method('validateArchive');

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'archive requirements failed'
        );

        (new ValidateRestoreContextStep(
            $databaseDriver,
            $archiveDriver
        ))->execute($context);
    }

    public function testStopsWhenArchiveIsInvalid(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $databaseDriver
            ->expects($this->once())
            ->method('validateRequirements');

        $databaseDriver
            ->expects($this->once())
            ->method('validateDump');

        $archiveDriver
            ->expects($this->once())
            ->method('validateRequirements');

        $archiveDriver
            ->expects($this->once())
            ->method('validateArchive')
            ->willThrowException(
                new RuntimeException('invalid archive')
            );

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid archive');

        (new ValidateRestoreContextStep(
            $databaseDriver,
            $archiveDriver
        ))->execute($context);
    }

    public function testHasExpectedName(): void
    {
        $step = new ValidateRestoreContextStep(
            $this->createMock(DatabaseBackupDriver::class),
            $this->createMock(ArchiveDriver::class)
        );

        $this->assertSame(
            'Validiere den Context für die Wiederherstellung',
            $step->name()
        );
    }
}