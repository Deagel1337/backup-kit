<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Backup\CleanupBackupStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CleanupBackupStepTest extends TestCase
{
    /**
     * Paths of temporary created files
     *
     * @var array<string>
     */
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

    public function test_returns_correct_name(): void
    {
        $step = new CleanupBackupStep;

        $this->assertSame(
            'Temporäre Datein für das Backup löschen',
            $step->name()
        );
    }

    public function test_deletes_dump_file(): void
    {
        $file = $this->createTemporaryFile();

        $dump = new DatabaseDump(
            $file,
            'test',
            'sql'
        );

        $context = new BackupContext(
            dump: $dump
        );

        $step = new CleanupBackupStep;

        $step->execute($context);

        $this->assertFileDoesNotExist($file);
    }

    public function test_throws_exception_when_dump_file_does_not_exist(): void
    {
        $file = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'cleanup_backup_test_nonexistent.sql';

        $dump = new DatabaseDump(
            $file,
            'test',
            'sql'
        );

        $context = new BackupContext(
            dump: $dump
        );

        $step = new CleanupBackupStep;

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Das Backup konnte nicht gelöscht werden.'
        );

        $step->execute($context);
    }

    private function createTemporaryFile(): string
    {
        $file = tempnam(
            sys_get_temp_dir(),
            'cleanup_backup_test_'
        );

        $this->assertNotFalse($file);

        $this->temporaryFiles[] = $file;

        return $file;
    }
}
