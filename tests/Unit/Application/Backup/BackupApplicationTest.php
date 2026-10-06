<?php

namespace Tests\Unit\Application\Backup;

use Deagel1337\Backup\Kit\Application\Backup\BackupApplication;
use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupApplicationTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'backup_app_').'.sql';
        file_put_contents($this->dumpPath, 'dump');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }

        parent::tearDown();
    }

    public function test_creates_dump_archives_it_and_removes_dump(): void
    {
        $archive = new ArchiveInfo('repo::backup', 'borg', 'borg');
        $archiveDriver = $this->createMock(ArchiveDriver::class);
        $archiveDriver
            ->expects($this->once())
            ->method('createArchive')
            ->with([$this->dumpPath, '/app'], 'backup')
            ->willReturn($archive);
        $archiveDriver->expects($this->never())->method('prune');

        $result = (new BackupApplication($this->databaseDriver(), new ArchiveService($archiveDriver)))
            ->run($this->dumpPath, 'backup', ['/app']);

        $this->assertSame($archive, $result->archive);
        $this->assertSame($this->dumpPath, $result->dump?->path);
        $this->assertTrue($result->dumpRemoved);
        $this->assertFalse($result->pruned);
        $this->assertGreaterThanOrEqual(0.0, $result->duration());
        $this->assertFileDoesNotExist($this->dumpPath);
    }

    public function test_keeps_dump_when_requested(): void
    {
        (new BackupApplication($this->databaseDriver(), new ArchiveService($this->archiveDriver())))
            ->run($this->dumpPath, 'backup', removeDump: false);

        $this->assertFileExists($this->dumpPath);
    }

    public function test_prunes_after_creating_archive_when_retention_is_given(): void
    {
        $archiveDriver = $this->archiveDriver();
        $archiveDriver
            ->expects($this->once())
            ->method('prune')
            ->with(5, null, null, 6, null);

        $result = (new BackupApplication($this->databaseDriver(), new ArchiveService($archiveDriver)))
            ->run($this->dumpPath, 'backup', retention: new RetentionPolicy(keepLast: 5, keepMonthly: 6));

        $this->assertTrue($result->pruned);
    }

    public function test_reports_progress_to_given_reporter(): void
    {
        $reporter = $this->createMock(ProgressReporter::class);
        $reporter->expects($this->once())->method('started')->with(3);
        $reporter->expects($this->exactly(3))->method('stepFinished');
        $reporter->expects($this->once())->method('finished');

        (new BackupApplication($this->databaseDriver(), new ArchiveService($this->archiveDriver()), $reporter))
            ->run($this->dumpPath, 'backup');
    }

    public function test_failed_step_stops_the_workflow_and_is_reported(): void
    {
        $database = $this->createMock(DatabaseBackupDriver::class);
        $database->method('createDump')->willThrowException(new RuntimeException('Dump failed'));

        $archiveDriver = $this->createMock(ArchiveDriver::class);
        $archiveDriver->expects($this->never())->method('createArchive');

        $reporter = $this->createMock(ProgressReporter::class);
        $reporter->expects($this->once())->method('stepFailed');
        $reporter->expects($this->never())->method('finished');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dump failed');

        (new BackupApplication($database, new ArchiveService($archiveDriver), $reporter))
            ->run($this->dumpPath, 'backup');
    }

    private function databaseDriver(): DatabaseBackupDriver
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);
        $driver
            ->expects($this->once())
            ->method('createDump')
            ->with($this->dumpPath)
            ->willReturn(new DatabaseDump($this->dumpPath, 'mariadb', 'sql'));

        return $driver;
    }

    private function archiveDriver(): ArchiveDriver
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->method('createArchive')->willReturn(new ArchiveInfo('repo::backup', 'borg', 'borg'));

        return $driver;
    }
}
