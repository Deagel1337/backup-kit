<?php

namespace Tests\Unit\Step\Restore;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Step\Restore\CheckBackupExistsStep;
use PHPUnit\Framework\TestCase;

final class CheckBackupExistsStepTest extends TestCase
{
    public function testReturnsCorrectName(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);

        $step = new CheckBackupExistsStep($archiveDriver);

        $this->assertSame(
            'Überprüf, ob das Backup vorhanden ist.',
            $step->name()
        );
    }

    public function testChecksBackupWithArchiveDriver(): void
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
            ->method('listContent')
            ->with($archive);

        $step = new CheckBackupExistsStep($archiveDriver);

        $step->execute($context);
    }
}