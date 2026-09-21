<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Archive;

use Backup\Php\Application\Archive\CreateBorgBackupApplication;
use Backup\Php\Archive\Driver\BorgArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Process\Runner\ProcOpenProcessRunner;
use PHPUnit\Framework\TestCase;

final class CreateBorgBackupApplicationTest extends TestCase
{
    private string $repository;
    private string $sourceDirectory;

    private ProcOpenProcessRunner $processRunner;

    protected function setUp(): void
    {
        $this->repository = sys_get_temp_dir()
            . '/borg-test-repository-' . bin2hex(random_bytes(8));

        $this->sourceDirectory = sys_get_temp_dir()
            . '/borg-test-source-' . bin2hex(random_bytes(8));

        mkdir($this->repository, 0700, true);
        mkdir($this->sourceDirectory, 0700, true);

        $this->processRunner = new ProcOpenProcessRunner();

        $this->initializeRepository();
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->repository);
        $this->removeDirectory($this->sourceDirectory);
    }

    public function test_it_creates_a_borg_backup(): void
    {
        $driver = new BorgArchiveDriver(
            repository: $this->repository,
            passphrase: '',
            sshPort: 22,
            process: $this->processRunner,
        );

        $application = new CreateBorgBackupApplication(
            archive: $driver,
        );

        $archive = $application->run(
            paths: [
                $this->sourceDirectory . '/test1.txt',
                $this->sourceDirectory . '/test2.txt',
                $this->sourceDirectory . '/test3.txt',
            ],
            name: 'test-backup',
        );

        self::assertInstanceOf(
            ArchiveInfo::class,
            $archive,
        );

        self::assertStringContainsString(
            'test-backup',
            $archive->path,
        );
    }

    public function test_it_lists_created_archive_content(): void
    {
        $driver = new BorgArchiveDriver(
            repository: $this->repository,
            passphrase: '',
            sshPort: 22,
            process: $this->processRunner,
        );

        $application = new CreateBorgBackupApplication(
            archive: $driver,
        );

        $archive = $application->run(
            paths: [
                $this->sourceDirectory . '/test1.txt',
                $this->sourceDirectory . '/test2.txt',
                $this->sourceDirectory . '/test3.txt',
            ],
            name: 'test-backup',
        );

        $application->listBorgArchiveContent($archive);

        self::assertTrue(true);
    }

    private function initializeRepository(): void
    {
        $result = $this->processRunner->run([
            'borg',
            'init',
            '--encryption=none',
            $this->repository,
        ]);

        self::assertSame(0, $result->exitCode);
    }

    private function createTestFiles(): void
    {
        file_put_contents(
            $this->sourceDirectory . '/test1.txt',
            'Test file 1',
        );

        file_put_contents(
            $this->sourceDirectory . '/test2.txt',
            'Test file 2',
        );

        file_put_contents(
            $this->sourceDirectory . '/test3.txt',
            'Test file 3',
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}