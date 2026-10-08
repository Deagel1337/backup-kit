<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Step\Backup\VerifyBackupArchiveStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class VerifyBackupArchiveStepTest extends TestCase
{
    public function test_accepts_archive_containing_sources_and_dump(): void
    {
        $archive = new ArchiveInfo('repo::backup', 'borg', 'borg');
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->once())->method('validateRequirements');
        $driver->expects($this->once())->method('validateArchive')->with($archive);
        $driver->expects($this->once())
            ->method('listArchive')
            ->with($archive)
            ->willReturn([
                new ArchiveEntry('app/config.php', 12, ArchiveEntryType::File),
                new ArchiveEntry('db/dump.sql', 12, ArchiveEntryType::File),
            ]);

        (new VerifyBackupArchiveStep(new ArchiveService($driver)))->execute(new BackupContext(
            dump: new DatabaseDump('/tmp/dump.sql', 'mariadb', 'sql'),
            archive: $archive,
            files: ['/var/www/config.php'],
        ));

        $this->addToAssertionCount(1);
    }

    public function test_rejects_context_without_archive(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->once())->method('validateRequirements');
        $driver->expects($this->never())->method('listArchive');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Es wurde kein Archiv zum Überprüfen erstellt.');

        (new VerifyBackupArchiveStep(new ArchiveService($driver)))->execute(new BackupContext);
    }

    public function test_rejects_archive_missing_expected_source(): void
    {
        $archive = new ArchiveInfo('repo::backup', 'borg', 'borg');
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->once())->method('validateRequirements');
        $driver->expects($this->once())->method('validateArchive')->with($archive);
        $driver->expects($this->once())
            ->method('listArchive')
            ->with($archive)
            ->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Backup-Quelle fehlt im Archiv: /var/www/config.php');

        (new VerifyBackupArchiveStep(new ArchiveService($driver)))->execute(new BackupContext(
            archive: $archive,
            files: ['/var/www/config.php'],
        ));
    }
}
