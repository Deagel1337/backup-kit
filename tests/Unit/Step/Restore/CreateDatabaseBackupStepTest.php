<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\CreateDatabaseBackupStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CreateDatabaseBackupStepTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testReturnsCorrectName(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);

        $step = new CreateDatabaseBackupStep(
            $driver,
            'backup',
        );

        $this->assertSame(
            'Erstellt ein sicherheits Dump der Datenbank, bevor der Restore-Prozess losgeht.',
            $step->name()
        );
    }

    public function testCreatesDumpWithCorrectName(): void
    {
        $dump = $this->createExistingDump();

        $driver = $this->createMock(DatabaseBackupDriver::class);

        $driver
            ->expects($this->once())
            ->method('createDump')
            ->with('backup')
            ->willReturn($dump);

        $step = new CreateDatabaseBackupStep(
            $driver,
            'backup',
        );

        $context = $this->createRestoreContext();

        $step->execute($context);
    }

    public function testStoresCreatedDumpInContext(): void
    {
        $dump = $this->createExistingDump();

        $driver = $this->createMock(DatabaseBackupDriver::class);

        $driver
            ->expects($this->once())
            ->method('createDump')
            ->willReturn($dump);

        $step = new CreateDatabaseBackupStep(
            $driver,
            'backup',
        );

        $context = $this->createRestoreContext();

        $step->execute($context);

        $this->assertSame(
            $dump,
            $context->rollbackDump
        );
    }

    public function testThrowsExceptionWhenDumpDoesNotExist(): void
    {
        $dump = new DatabaseDump(
            '/this/file/does/not/exist/dump.sql',
            'test',
            'sql'
        );

        $driver = $this->createMock(DatabaseBackupDriver::class);

        $driver
            ->expects($this->once())
            ->method('createDump')
            ->with('backup')
            ->willReturn($dump);

        $step = new CreateDatabaseBackupStep(
            $driver,
            'backup',
        );

        $context = $this->createRestoreContext();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Konnte kein Datenbank Dump erstellen.'
        );

        $step->execute($context);
    }

    private function createExistingDump(): DatabaseDump
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'restore_dump_test_'
        );

        $this->assertNotFalse($path);

        $this->temporaryFiles[] = $path;

        return new DatabaseDump(
            $path,
            'test',
            'sql'
        );
    }

    private function createRestoreContext(): RestoreContext
    {
        $archive = new ArchiveInfo(
            '/tmp/backup.zip',
            'test',
            'zip'
        );

        $dump = new DatabaseDump(
            '/tmp/old-dump.sql',
            'test',
            'sql'
        );

        return new RestoreContext(
            $archive,
            $dump,
            '/tmp/restore'
        );
    }
}
