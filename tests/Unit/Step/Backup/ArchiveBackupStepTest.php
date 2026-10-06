<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Step\Backup\ArchiveBackupStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArchiveBackupStepTest extends TestCase
{
    public function test_returns_name(): void
    {
        $step = new ArchiveBackupStep(new ArchiveService($this->createStub(ArchiveDriver::class)), 'backup');

        $this->assertSame('Backup archivieren', $step->name());
    }

    public function test_archives_dump_and_additional_files(): void
    {
        $archive = new ArchiveInfo('repo::backup', 'borg', 'borg');
        $driver = $this->createMock(ArchiveDriver::class);
        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->with(['/tmp/dump.sql', '/app', '/config'], 'backup')
            ->willReturn($archive);

        $context = new BackupContext(
            dump: new DatabaseDump('/tmp/dump.sql', 'mariadb', 'sql'),
            files: ['/app', '/config'],
        );

        (new ArchiveBackupStep(new ArchiveService($driver), 'backup'))->execute($context);

        $this->assertSame($archive, $context->archive);
    }

    public function test_archives_only_files_without_dump(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->with(['/app'], 'backup')
            ->willReturn(new ArchiveInfo('repo::backup', 'borg', 'borg'));

        (new ArchiveBackupStep(new ArchiveService($driver), 'backup'))
            ->execute(new BackupContext(files: ['/app']));
    }

    public function test_rejects_empty_input(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->never())->method('createArchive');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Es gibt nichts zu archivieren.');

        (new ArchiveBackupStep(new ArchiveService($driver), 'backup'))->execute(new BackupContext);
    }
}
