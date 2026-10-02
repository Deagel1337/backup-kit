<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Backup\CreateBackupDirectoryStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CreateBackupDirectoryStepTest extends TestCase
{
    /**
     * Paths of temporary directories
     *
     * @var array<string>
     */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }

        parent::tearDown();
    }

    public function test_returns_correct_name(): void
    {
        $step = new CreateBackupDirectoryStep;

        $this->assertSame(
            'Backup-Verzeichnis erstellen',
            $step->name()
        );
    }

    public function test_creates_backup_directory(): void
    {
        $directory = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'backup_test_'
            .uniqid();

        $this->temporaryDirectories[] = $directory;

        $context = new BackupContext(
            destination: $directory
        );

        $step = new CreateBackupDirectoryStep;

        $step->execute($context);

        $this->assertDirectoryExists($directory);
    }

    public function test_throws_exception_when_directory_cannot_be_created(): void
    {
        $file = tempnam(
            sys_get_temp_dir(),
            'backup_test_'
        );

        $this->assertNotFalse($file);

        try {
            $context = new BackupContext(
                destination: $file
            );

            $step = new CreateBackupDirectoryStep;

            $this->expectException(RuntimeException::class);

            $this->expectExceptionMessage(
                'Konnte das Backup-Verzeichnis nicht erstellen'
            );

            $step->execute($context);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
