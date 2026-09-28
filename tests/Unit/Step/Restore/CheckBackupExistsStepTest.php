<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\CheckBackupExistsStep;
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