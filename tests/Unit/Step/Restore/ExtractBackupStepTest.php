<?php

namespace Tests\Unit\Step\Restore;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Step\Restore\ExtractBackupStep;
use PHPUnit\Framework\TestCase;

final class ExtractBackupStepTest extends TestCase
{
    public function testReturnsCorrectName(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $step = new ExtractBackupStep($archiveDriver);

        $this->assertSame(
            'Extrahiert die Backupdateien.',
            $step->name()
        );
    }

    public function testExtractsBackupToConfiguredDestination(): void
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

    public function testUsesEmptyDestinationByDefault(): void
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
