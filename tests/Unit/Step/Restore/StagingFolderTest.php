<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Restore\CleanStagingFolder;
use Deagel1337\Backup\Kit\Step\Restore\CommitStagingStep;
use Deagel1337\Backup\Kit\Step\Restore\CreateStagingFolder;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDirectoryCleaner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StagingFolderTest extends TestCase
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

    public function test_creates_unique_staging_directory_next_to_destination(): void
    {
        $root = $this->createTemporaryDirectory();
        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'borg', 'borg'),
            null,
            $root.'/restore',
        );

        (new CreateStagingFolder)->execute($context);

        $this->assertNotNull($context->stagingDestination);
        $this->assertSame($root, dirname($context->stagingDestination));
        $this->assertDirectoryExists($context->stagingDestination);
        $this->assertTrue($context->stagingCreated);

        $stagingDestination = $context->stagingDestination;
        (new CleanStagingFolder)->execute($context);

        $this->assertDirectoryDoesNotExist($stagingDestination);
        $this->assertFalse($context->stagingCreated);
        $this->assertNull($context->stagingDestination);
    }

    public function test_replaces_existing_destination_only_after_staging_exists(): void
    {
        $root = $this->createTemporaryDirectory();
        $destination = $root.'/restore';
        $stagingDestination = $root.'/staging';
        mkdir($destination, 0700);
        mkdir($stagingDestination, 0700);
        file_put_contents($destination.'/old.txt', 'old');
        file_put_contents($stagingDestination.'/new.txt', 'new');
        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'borg', 'borg'),
            null,
            $destination,
            stagingDestination: $stagingDestination,
            stagingCreated: true,
        );

        (new CommitStagingStep)->execute($context);

        $this->assertFileDoesNotExist($destination.'/old.txt');
        $this->assertSame('new', file_get_contents($destination.'/new.txt'));
        $this->assertDirectoryDoesNotExist($stagingDestination);
        $this->assertTrue($context->archiveRestoreCommitted);
        $this->assertFalse($context->stagingCreated);
        $this->assertNull($context->stagingDestination);
        $this->assertSame([$destination], glob($root.'/*'));
    }

    public function test_commit_failure_leaves_existing_destination_untouched(): void
    {
        $root = $this->createTemporaryDirectory();
        $destination = $root.'/restore';
        mkdir($destination, 0700);
        file_put_contents($destination.'/old.txt', 'old');
        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'borg', 'borg'),
            null,
            $destination,
            stagingDestination: $root.'/staging',
        );

        try {
            (new CommitStagingStep)->execute($context);
            $this->fail('Expected commit failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Staging-Verzeichnis', $exception->getMessage());
        }

        $this->assertSame('old', file_get_contents($destination.'/old.txt'));
        $this->assertFalse($context->archiveRestoreCommitted);
    }

    public function test_clean_step_does_not_remove_unowned_directory(): void
    {
        $root = $this->createTemporaryDirectory();
        $stagingDestination = $root.'/staging';
        mkdir($stagingDestination, 0700);
        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'borg', 'borg'),
            null,
            $root.'/restore',
            stagingDestination: $stagingDestination,
        );

        (new CleanStagingFolder)->execute($context);

        $this->assertDirectoryExists($stagingDestination);
    }

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/restore_staging_test_'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}
