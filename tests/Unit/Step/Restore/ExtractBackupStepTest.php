<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\ExtractBackupStep;
use PHPUnit\Framework\TestCase;

final class ExtractBackupStepTest extends TestCase
{
    public function test_returns_correct_name(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $step = new ExtractBackupStep($archiveDriver);

        $this->assertSame(
            'Extrahiert die Backupdateien.',
            $step->name()
        );
    }

    public function test_extracts_backup_to_configured_destination(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $archive = new ArchiveInfo(
            '/tmp/backup.zip',
            'test',
            'zip'
        );

        $dump = new DatabaseDump(
            '/tmp/dump.sql',
            'test',
            'sql'
        );

        $context = new RestoreContext(
            $archive,
            $dump,
            '/tmp/restore'
        );

        $destination = '/tmp/extracted-backup';

        $archiveDriver
            ->expects($this->once())
            ->method('extractArchive')
            ->with(
                $archive,
                $destination
            );

        $step = new ExtractBackupStep(
            $archiveDriver,
            $destination
        );

        $step->execute($context);
    }

    public function test_uses_empty_destination_by_default(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $archive = new ArchiveInfo(
            '/tmp/backup.zip',
            'test',
            'zip'
        );

        $dump = new DatabaseDump(
            '/tmp/dump.sql',
            'test',
            'sql'
        );

        $context = new RestoreContext(
            $archive,
            $dump,
            '/tmp/restore'
        );

        $archiveDriver
            ->expects($this->once())
            ->method('extractArchive')
            ->with(
                $archive,
                ''
            );

        $step = new ExtractBackupStep($archiveDriver);

        $step->execute($context);
    }
}
