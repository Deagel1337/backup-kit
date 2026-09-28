<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Backup\BackupApplicationFilesStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupApplicationFilesStepTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testReturnsCorrectName(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $step = new BackupApplicationFilesStep(
            $driver,
            'backup.zip',
            ['/app', '/config']
        );

        $this->assertSame(
            'Anwendungsdateien sichern',
            $step->name()
        );
    }

    public function testCreatesArchiveWithCorrectPathsAndName(): void
    {
        $archive = $this->createExistingArchive();

        $driver = $this->createMock(ArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->with(
                ['/app', '/config'],
                'backup.zip'
            )
            ->willReturn($archive);

        $step = new BackupApplicationFilesStep(
            $driver,
            'backup.zip',
            ['/app', '/config']
        );

        $context = new BackupContext();

        $step->execute($context);
    }

    public function testStoresCreatedArchiveInContext(): void
    {
        $archive = $this->createExistingArchive();

        $driver = $this->createMock(ArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->willReturn($archive);

        $step = new BackupApplicationFilesStep(
            $driver,
            'backup.zip',
            ['/app']
        );

        $context = new BackupContext();

        $step->execute($context);

        $this->assertSame(
            $archive,
            $context->archive
        );
    }

    public function testThrowsExceptionWhenArchiveDoesNotExist(): void
    {
        $archive = new ArchiveInfo(
            '/this/file/does/not/exist/backup.zip',
            'test',
            'zip'
        );

        $driver = $this->createMock(ArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->willReturn($archive);

        $step = new BackupApplicationFilesStep(
            $driver,
            'backup.zip',
            ['/app']
        );

        $context = new BackupContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Das Erstellen eines Backups-Archives ist fehlgeschlagen'
        );

        $step->execute($context);
    }

    private function createExistingArchive(): ArchiveInfo
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'backup_test_'
        );

        $this->assertNotFalse($path);

        $this->temporaryFiles[] = $path;

        return new ArchiveInfo(
            $path,
            'test',
            'zip'
        );
    }
}
