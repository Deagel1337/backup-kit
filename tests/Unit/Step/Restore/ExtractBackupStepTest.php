<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Restore\CreateStagingFolder;
use Deagel1337\Backup\Kit\Step\Restore\ExtractBackupStep;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDirectoryCleaner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtractBackupStepTest extends TestCase
{
    /**
     * @var array<string>
     */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            RestoreDirectoryCleaner::remove($directory);
        }

        parent::tearDown();
    }

    public function test_returns_correct_name(): void
    {
        $step = new ExtractBackupStep($this->createStub(ArchiveDriver::class));

        $this->assertSame('Extrahiert die Backupdateien.', $step->name());
    }

    public function test_rejects_destination_override_after_staging_was_created(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);
        $root = $this->createTemporaryDirectory();
        $overrideRoot = $this->createTemporaryDirectory();
        $archive = new ArchiveInfo('/tmp/backup.zip', 'test', 'zip');
        $context = new RestoreContext($archive, null, $root.'/restore');

        (new CreateStagingFolder)->execute($context);

        $archiveDriver->expects($this->never())->method('extractArchive');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Das Restore-Ziel muss vor der Validierung');
        (new ExtractBackupStep($archiveDriver, $overrideRoot.'/extracted-backup'))->execute($context);
    }

    public function test_uses_context_destination_by_default(): void
    {
        $archiveDriver = $this->createMock(ArchiveDriver::class);
        $root = $this->createTemporaryDirectory();
        $archive = new ArchiveInfo('/tmp/backup.zip', 'test', 'zip');
        $context = new RestoreContext($archive, null, $root.'/restore');

        (new CreateStagingFolder)->execute($context);

        $archiveDriver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, $context->stagingDestination);

        (new ExtractBackupStep($archiveDriver))->execute($context);
    }

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/extract_backup_test_'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}
