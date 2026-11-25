<?php

namespace Tests\Unit\Services;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Services\RestoreService;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
use Deagel1337\Backup\Kit\Step\Restore\CleanStagingFolder;
use Deagel1337\Backup\Kit\Step\Restore\CommitStagingStep;
use Deagel1337\Backup\Kit\Step\Restore\CreateStagingFolder;
use Deagel1337\Backup\Kit\Step\Restore\RestoreArchiveStep;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDirectoryCleaner;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\RestoreRollbackHandler;
use Deagel1337\Backup\Kit\Step\Restore\ValidateRestoreContextStep;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestoreStagingFlowTest extends TestCase
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

    public function test_replaces_existing_destination_after_successful_extraction(): void
    {
        $root = $this->createTemporaryDirectory();
        $destination = $root.'/restore';
        mkdir($destination, 0700);
        file_put_contents($destination.'/old.txt', 'old');
        $archive = new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz');
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->once())->method('validateArchive')->with($archive);
        $driver->expects($this->once())->method('listArchive')
            ->willReturn([new ArchiveEntry('new.txt', 3, ArchiveEntryType::File)]);
        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, $this->callback(fn (string $path): bool => dirname($path) === $root))
            ->willReturnCallback(function (ArchiveInfo $archive, string $path): void {
                if (file_put_contents($path.'/new.txt', 'new') === false) {
                    throw new RuntimeException('Could not write staged file.');
                }
            });

        $context = new RestoreContext($archive, null, $root.'/original');
        $rollback = $this->createMock(RestoreRollbackHandler::class);
        $rollback->expects($this->never())->method('rollback');
        $service = new RestoreService(
            [
                new ValidateRestoreContextStep($this->createStub(DatabaseBackupDriver::class), $driver, $destination),
                new CreateStagingFolder,
                new RestoreArchiveStep($driver, $destination),
                new CommitStagingStep,
            ],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $rollback,
            [new CleanStagingFolder],
        );

        $service->restore($context);

        $this->assertFileDoesNotExist($destination.'/old.txt');
        $this->assertSame('new', file_get_contents($destination.'/new.txt'));
        $this->assertTrue($context->archiveRestoreCommitted);
        $this->assertNull($context->stagingDestination);
    }

    public function test_preserves_existing_destination_when_extraction_fails(): void
    {
        $root = $this->createTemporaryDirectory();
        $destination = $root.'/restore';
        mkdir($destination, 0700);
        file_put_contents($destination.'/old.txt', 'old');
        $archive = new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz');
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->method('listArchive')
            ->willReturn([new ArchiveEntry('new.txt', 3, ArchiveEntryType::File)]);
        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->willThrowException(new RuntimeException('extract failed'));

        $context = new RestoreContext($archive, null, $destination);
        $service = new RestoreService(
            [
                new ValidateRestoreContextStep($this->createStub(DatabaseBackupDriver::class), $driver),
                new CreateStagingFolder,
                new RestoreArchiveStep($driver),
                new CommitStagingStep,
            ],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $this->createStub(RestoreRollbackHandler::class),
            [new CleanStagingFolder],
        );

        try {
            $service->restore($context);
            $this->fail('Expected extraction failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('extract failed', $exception->getMessage());
        }

        $this->assertSame('old', file_get_contents($destination.'/old.txt'));
        $this->assertSame([], glob($root.'/.restore.restore-*'));
        $this->assertFalse($context->archiveRestoreCommitted);
    }

    public function test_staging_can_run_without_validation_step(): void
    {
        $root = $this->createTemporaryDirectory();
        $context = new RestoreContext(new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'), null, $root.'/restore');
        $service = new RestoreService(
            [new CreateStagingFolder],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $this->createStub(RestoreRollbackHandler::class),
        );

        $service->restore($context);
        $this->assertTrue($context->stagingCreated);
        $this->assertDirectoryExists($context->stagingDestination);
    }

    public function test_validation_can_follow_other_steps(): void
    {
        $root = $this->createTemporaryDirectory();
        $context = new RestoreContext(new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'), null, $root.'/restore');
        $driver = $this->createStub(ArchiveDriver::class);
        $driver->method('listArchive')
            ->willReturn([new ArchiveEntry('file.txt', 1, ArchiveEntryType::File)]);
        $first = $this->createStub(RestoreStep::class);
        $first->method('name')->willReturn('Vorbereitung');
        $service = new RestoreService(
            [
                $first,
                new ValidateRestoreContextStep(
                    $this->createStub(DatabaseBackupDriver::class),
                    $driver,
                ),
            ],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $this->createStub(RestoreRollbackHandler::class),
        );

        $service->restore($context);
        $this->assertSame($root.'/restore', $context->destination);
    }

    public function test_invalid_archive_is_rejected_before_staging_and_extraction(): void
    {
        $root = $this->createTemporaryDirectory();
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->method('listArchive')
            ->willReturn([new ArchiveEntry('../outside', 1, ArchiveEntryType::File)]);
        $driver->expects($this->never())->method('extractArchive');
        $context = new RestoreContext(new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'), null, $root.'/restore');
        $service = new RestoreService(
            [
                new ValidateRestoreContextStep($this->createStub(DatabaseBackupDriver::class), $driver),
                new CreateStagingFolder,
                new RestoreArchiveStep($driver),
            ],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $this->createStub(RestoreRollbackHandler::class),
        );

        try {
            $service->restore($context);
            $this->fail('Expected invalid archive to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('unsicheren Pfad', $exception->getMessage());
        }

        $this->assertNull($context->stagingDestination);
        $this->assertSame([], glob($root.'/.restore.restore-*'));
    }

    public function test_allows_destination_changed_by_custom_step(): void
    {
        $root = $this->createTemporaryDirectory();
        $driver = $this->createStub(ArchiveDriver::class);
        $driver->method('listArchive')
            ->willReturn([new ArchiveEntry('file.txt', 1, ArchiveEntryType::File)]);
        $changeDestination = $this->createStub(RestoreStep::class);
        $changeDestination->method('name')->willReturn('Ziel ändern');
        $changeDestination->method('execute')->willReturnCallback(
            static function (RestoreContext $context) use ($root): void {
                $context->destination = $root.'/other';
            }
        );
        $context = new RestoreContext(new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'), null, $root.'/restore');
        $service = new RestoreService(
            [
                new ValidateRestoreContextStep($this->createStub(DatabaseBackupDriver::class), $driver),
                $changeDestination,
                new CreateStagingFolder,
            ],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $this->createStub(RestoreRollbackHandler::class),
        );

        $service->restore($context);
        $this->assertSame($root.'/other', $context->destination);
        $this->assertTrue($context->stagingCreated);
    }

    public function test_staging_created_after_validation_is_not_overwritten_or_removed(): void
    {
        $root = $this->createTemporaryDirectory();
        $staging = $root.'/staging';
        $driver = $this->createStub(ArchiveDriver::class);
        $driver->method('listArchive')
            ->willReturn([new ArchiveEntry('file.txt', 1, ArchiveEntryType::File)]);
        $occupyStaging = $this->createStub(RestoreStep::class);
        $occupyStaging->method('name')->willReturn('Staging belegen');
        $occupyStaging->method('execute')->willReturnCallback(
            static function () use ($staging): void {
                file_put_contents($staging, 'existing');
            }
        );
        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            null,
            $root.'/restore',
            stagingDestination: $staging,
        );
        $service = new RestoreService(
            [
                new ValidateRestoreContextStep($this->createStub(DatabaseBackupDriver::class), $driver),
                $occupyStaging,
                new CreateStagingFolder,
            ],
            new StepRunner($this->createStub(ProgressReporter::class)),
            $this->createStub(RestoreRollbackHandler::class),
        );

        try {
            $service->restore($context);
            $this->fail('Expected staging creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('konnte nicht erstellt werden', $exception->getMessage());
        }

        $this->assertSame('existing', file_get_contents($staging));
        $this->assertFalse($context->stagingCreated);
    }

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/restore_flow_test_'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}
