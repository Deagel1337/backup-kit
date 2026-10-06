<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\ValidateRestoreContextStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ValidateRestoreContextStepTest extends TestCase
{
    public function test_validates_requirements_and_context_in_order(): void
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
                    $this->assertSame(2, $step++);
                }
            );

        $archiveDriver
            ->expects($this->once())
            ->method('validateRequirements')
            ->willReturnCallback(
                function () use (&$step): void {
                    $this->assertSame(1, $step++);
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
        $archiveDriver
            ->expects($this->once())
            ->method('listArchive')
            ->with($archive)
            ->willReturn([new ArchiveEntry('dump.sql', 4, ArchiveEntryType::File)]);

        (new ValidateRestoreContextStep(
            $databaseDriver,
            $archiveDriver
        ))->execute($context);

        $this->assertSame(4, $step);
    }

    public function test_stops_when_database_requirements_are_invalid(): void
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

    public function test_stops_when_database_dump_is_invalid(): void
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
            ->expects($this->once())
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

    public function test_stops_when_archive_requirements_are_invalid(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $databaseDriver
            ->expects($this->once())
            ->method('validateRequirements');

        $databaseDriver
            ->expects($this->never())
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

    public function test_stops_when_archive_is_invalid(): void
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

    public function test_rejects_archive_entries_that_escape_destination(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $archiveDriver = $this->createMock(ArchiveDriver::class);
        $archive = new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz');
        $archiveDriver
            ->method('listArchive')
            ->willReturn([new ArchiveEntry('../outside.txt', 4, ArchiveEntryType::File)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Das Archiv enthält einen unsicheren Pfad');

        (new ValidateRestoreContextStep($databaseDriver, $archiveDriver))->execute(
            new RestoreContext($archive, null, '/tmp/restore')
        );
    }

    public function test_skips_archive_requirements_for_database_only_restore(): void
    {
        $databaseDriver = $this->createMock(DatabaseBackupDriver::class);
        $databaseDriver->expects($this->once())->method('validateRequirements');
        $archiveDriver = $this->createMock(ArchiveDriver::class);
        $archiveDriver->expects($this->never())->method('validateRequirements');

        (new ValidateRestoreContextStep($databaseDriver, $archiveDriver))->execute(
            new RestoreContext(null, null, '')
        );
    }

    public function test_has_expected_name(): void
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
