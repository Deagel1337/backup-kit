<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Backup\ValidateBackupContextStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ValidateBackupContextStepTest extends TestCase
{
    public function test_validates_requirements_sources_and_existing_dump(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'backup-source-');
        $dumpPath = tempnam(sys_get_temp_dir(), 'backup-dump-');
        self::assertNotFalse($source);
        self::assertNotFalse($dumpPath);
        $dump = new DatabaseDump($dumpPath, 'mariadb', 'sql');

        try {
            $driver = $this->createMock(DatabaseBackupDriver::class);
            $driver->expects($this->once())->method('validateRequirements');
            $driver->expects($this->once())->method('validateDump')->with($dump);

            (new ValidateBackupContextStep($driver))->execute(new BackupContext(
                destination: sys_get_temp_dir().'/backup.sql',
                dump: $dump,
                files: [$source],
            ));
        } finally {
            unlink($source);
            unlink($dumpPath);
        }
    }

    public function test_rejects_unwritable_destination_directory(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);
        $driver->expects($this->once())->method('validateRequirements');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Das Zielverzeichnis für den Datenbank-Dump ist nicht beschreibbar.');

        (new ValidateBackupContextStep($driver))->execute(new BackupContext(
            destination: '/path/that/does/not/exist/backup.sql',
        ));
    }

    public function test_rejects_missing_backup_source(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);
        $driver->expects($this->once())->method('validateRequirements');
        $driver->expects($this->never())->method('validateDump');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Backup-Quelle ist nicht vorhanden oder nicht lesbar: /missing/source');

        (new ValidateBackupContextStep($driver))->execute(new BackupContext(
            destination: sys_get_temp_dir().'/backup.sql',
            files: ['/missing/source'],
        ));
    }
}
