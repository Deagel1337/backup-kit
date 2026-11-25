<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntry;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveEntryType;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Console\BorgListArchiveContentCommand;
use Deagel1337\Backup\Kit\Console\BorgListCommand;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class BorgListCommandsTest extends TestCase
{
    public function test_lists_archives_from_configured_repository(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->once())->method('validateRequirements');
        $driver->expects($this->once())
            ->method('listArchives')
            ->willReturn([
                new ArchiveInfo('/repo::backup-2026', 'borg', 'borg'),
            ]);

        $hadRepository = array_key_exists('BORG_REPOSITORY', $_ENV);
        $previousRepository = $_ENV['BORG_REPOSITORY'] ?? null;
        $_ENV['BORG_REPOSITORY'] = '/repo';
        $output = new BufferedOutput;

        try {
            $exitCode = (new BorgListCommand(new ArchiveApplication(
                new ArchiveService($driver)
            )))->__invoke($output);
        } finally {
            $this->restoreRepositoryEnvironment($hadRepository, $previousRepository);
        }

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('/repo::backup-2026', $output->fetch());
    }

    public function test_lists_archive_content(): void
    {
        $archive = new ArchiveInfo('/repo::backup-2026', 'borg', 'borg');
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->once())->method('validateRequirements');
        $driver->expects($this->once())->method('validateArchive')->with($archive);
        $driver->expects($this->once())
            ->method('listArchive')
            ->with($archive)
            ->willReturn([
                new ArchiveEntry('var/www/index.php', 10, ArchiveEntryType::File),
            ]);

        $hadRepository = array_key_exists('BORG_REPOSITORY', $_ENV);
        $previousRepository = $_ENV['BORG_REPOSITORY'] ?? null;
        $_ENV['BORG_REPOSITORY'] = '/repo';
        $output = new BufferedOutput;

        try {
            $exitCode = (new BorgListArchiveContentCommand(new ArchiveApplication(
                new ArchiveService($driver)
            )))->__invoke($output, $archive->path);
        } finally {
            $this->restoreRepositoryEnvironment($hadRepository, $previousRepository);
        }

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('var/www/index.php', $output->fetch());
    }

    private function restoreRepositoryEnvironment(bool $hadRepository, ?string $repository): void
    {
        if ($hadRepository) {
            $_ENV['BORG_REPOSITORY'] = $repository;

            return;
        }

        unset($_ENV['BORG_REPOSITORY']);
    }
}
