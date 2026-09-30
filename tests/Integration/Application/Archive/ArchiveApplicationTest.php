<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Archive;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Driver\BorgArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Process\Runner\ProcOpenProcessRunner;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;

final class ArchiveApplicationTest extends TestCase
{
    private string $repository;
    private string $sourceDirectory;
    private ProcOpenProcessRunner $processRunner;

    private array $testFilesPaths = [];

    protected function setUp(): void
    {
        $this->repository = sys_get_temp_dir() . '/borg-test-repository-' . bin2hex(random_bytes(8));

        $this->sourceDirectory = sys_get_temp_dir() . '/borg-test-source-' . bin2hex(random_bytes(8));

        mkdir($this->repository, 0700, true);
        mkdir($this->sourceDirectory, 0700, true);

        $this->processRunner = new ProcOpenProcessRunner();

        $this->initializeRepository();
        $this->createTestFiles();
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
        $filePaths = [];
        
        for($i = 0; $i < 3; $i++) {
            $filePaths[$i] = sprintf("%s/test%s.txt", $this->sourceDirectory, $i);

            file_put_contents(
                $filePaths[$i],
                sprintf('Test file %s', $i)
            );
        }

        $this->testFilesPaths = $filePaths;
    }

    private function deleteTestFiles(): void
    {
        if(count($this->testFilesPaths) > 0) {
            foreach($this->testFilesPaths as $file) {
                unlink($file);
            }
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->repository);
        $this->removeDirectory($this->sourceDirectory);
        $this->deleteTestFiles();

        parent::tearDown();
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

    public function test_it_creates_a_borg_backup(): void
    {
        $driver = new BorgArchiveDriver(
            repository: $this->repository,
            passphrase: '',
            sshPort: 22,
            process: $this->processRunner,
        );

        $service = new ArchiveService($driver);

        $application = new ArchiveApplication($service);

        $archive = $application->run(
            paths: $this->testFilesPaths,
            name: 'test-borg-backup'
        );

        $this->assertInstanceOf(ArchiveInfo::class, $archive);

        $this->assertStringContainsString(
            'test-borg-backup',
            $archive->path,
        );
    }

    public function test_it_lists_contents_of_a_borg_backup(): void
    {
        $driver = new BorgArchiveDriver(
            repository: $this->repository,
            passphrase: '',
            sshPort: 22,
            process: $this->processRunner,
        );

        $service = new ArchiveService($driver);

        $application = new ArchiveApplication($service);

        $archive = $application->run(
            name: 'test-borg-backup', 
            paths: $this->testFilesPaths 
        );

        $this->assertInstanceOf(ArchiveInfo::class, $archive);

        $archiveContent = $application->list($archive);

        foreach($archiveContent as $index => $entry) {
            $expectedPath = ltrim($this->testFilesPaths[$index], '/');
            $this->assertEquals($expectedPath, $entry->path);
        }
    }

    public function test_it_lists_all_archives_in_a_repository(): void
    {
        $driver = new BorgArchiveDriver(
            repository: $this->repository,
            passphrase: '',
            sshPort: 22,
            process: $this->processRunner,
        );

        $service = new ArchiveService($driver);

        $application = new ArchiveApplication($service);

        $createdArchives = [];

        $createdArchives[] = $application->run(
            name: 'test-backup-1',
            paths: $this->testFilesPaths
        );

        $createdArchives[] = $application->run(
            name: 'test-backup-2',
            paths: $this->testFilesPaths,
        );

        foreach($createdArchives as $archive) {
            $this->assertInstanceOf(ArchiveInfo::class, $archive);
        }

        foreach($application->listAllArchives() as $archive) {
            $this->assertContainsEquals($archive, $createdArchives);            
        }
    }

    public function test_it_extracts_content_of_archive(): void
    {
        $driver = new BorgArchiveDriver(
            repository: $this->repository,
            passphrase: '',
            sshPort: 22,
            process: $this->processRunner,
        );

        $service = new ArchiveService($driver);

        $application = new ArchiveApplication($service);

        $archiveToExtract = $application->run(name: 'backup-to-extract', paths: $this->testFilesPaths);

        $this->assertInstanceOf(ArchiveInfo::class, $archiveToExtract);

        $application->extract($archiveToExtract, $this->sourceDirectory);

        $this->assertFileExists($this->testFilesPaths[0]);
    }
}