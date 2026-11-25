<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Restore\RestoreArchiveStep;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDirectoryCleaner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestoreArchiveStepTest extends TestCase
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

    public function test_extracts_context_archive_to_staging_destination(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $root = $this->createTemporaryDirectory();
        $archive = new ArchiveInfo('/tmp/backup.tar.gz', 'tar', 'tar.gz');
        $stagingDestination = $root.'/staging';
        mkdir($stagingDestination, 0700);
        $context = new RestoreContext(
            archive: $archive,
            dump: null,
            destination: $root.'/restore',
            stagingDestination: $stagingDestination,
            stagingCreated: true,
        );

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, $stagingDestination);

        (new RestoreArchiveStep($driver))->execute($context);
    }

    public function test_leaves_staging_cleanup_to_configured_steps_after_failure(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $root = $this->createTemporaryDirectory();
        $stagingDestination = $root.'/staging';
        mkdir($stagingDestination, 0700);
        file_put_contents($stagingDestination.'/partial.txt', 'partial');
        $context = new RestoreContext(
            archive: new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            dump: null,
            destination: $root.'/restore',
            stagingDestination: $stagingDestination,
            stagingCreated: true,
        );

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->willThrowException(new RuntimeException('extract failed'));

        try {
            (new RestoreArchiveStep($driver))->execute($context);
            $this->fail('Expected extraction failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('extract failed', $exception->getMessage());
        }

        $this->assertFileExists($stagingDestination.'/partial.txt');
        $this->assertTrue($context->stagingCreated);
        $this->assertSame($stagingDestination, $context->stagingDestination);
    }

    public function test_extracts_directly_into_existing_destination_without_staging(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $root = $this->createTemporaryDirectory();
        file_put_contents($root.'/old.txt', 'old');
        $archive = new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz');
        $driver->expects($this->once())->method('extractArchive')
            ->with($archive, $root)
            ->willReturnCallback(static function (ArchiveInfo $archive, string $destination): void {
                file_put_contents($destination.'/old.txt', 'new');
            });

        $context = new RestoreContext($archive, null, $root);
        (new RestoreArchiveStep($driver))->execute($context);

        $this->assertSame('new', file_get_contents($root.'/old.txt'));
        $this->assertTrue($context->archiveRestoreCommitted);
    }

    public function test_requires_configured_staging_to_be_created_before_extraction(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->never())->method('extractArchive');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Das Staging-Verzeichnis muss vor der Extraktion erstellt werden.');
        (new RestoreArchiveStep($driver))->execute(new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            null,
            '/tmp/restore',
            stagingDestination: '/tmp/staging',
        ));
    }

    public function test_direct_restore_uses_destination_override_and_creates_directory(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $root = $this->createTemporaryDirectory();
        $archive = new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz');
        $destination = $root.'/nested/restore';
        $driver->expects($this->once())->method('extractArchive')->with($archive, $destination);

        (new RestoreArchiveStep($driver, $destination))->execute(new RestoreContext($archive, null, $destination));

        $this->assertDirectoryExists($destination);
    }

    public function test_direct_restore_rejects_destination_override_mismatch(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->never())->method('extractArchive');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Das Restore-Ziel muss vor der Validierung im Context festgelegt werden.');

        (new RestoreArchiveStep($driver, '/validated/override'))->execute(
            new RestoreContext(
                new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
                null,
                '/different/validated/destination',
            )
        );
    }

    public function test_has_expected_name(): void
    {
        $this->assertSame(
            'Archiv wiederherstellen',
            (new RestoreArchiveStep($this->createStub(ArchiveDriver::class)))->name()
        );
    }

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/restore_archive_test_'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}
