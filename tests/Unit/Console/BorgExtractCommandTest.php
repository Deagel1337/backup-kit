<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Interfaces\RepositoryAwareArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Console\BorgExtractCommand;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class BorgExtractCommandTest extends TestCase
{
    public function test_extracts_archive_using_repository_configured_in_driver(): void
    {
        $archive = new ArchiveInfo('/configured/repository::backup-2026', 'borg', 'borg');
        $driver = $this->createMock(RepositoryAwareArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('listArchives')
            ->willReturn([$archive]);
        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, '.');

        $command = new BorgExtractCommand(
            new ArchiveApplication(new ArchiveService($driver))
        );
        $tester = new CommandTester($command);
        $tester->setInputs([$archive->path]);

        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Extraktion erfolgreich', $tester->getDisplay());
    }

    public function test_passes_path_and_strip_components_to_driver(): void
    {
        $archive = new ArchiveInfo('/configured/repository::backup-2026', 'borg', 'borg');
        $driver = $this->createMock(RepositoryAwareArchiveDriver::class);

        $driver->method('listArchives')->willReturn([$archive]);
        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, '/tmp/volume', ['var/www/wp-content', 'var/www/x'], 7);

        $tester = new CommandTester(new BorgExtractCommand(
            new ArchiveApplication(new ArchiveService($driver))
        ));
        $tester->setInputs([$archive->path]);

        $exitCode = $tester->execute([
            'destination' => '/tmp/volume',
            '--path' => ['var/www/wp-content', 'var/www/x'],
            '--strip-components' => '7',
        ]);

        $this->assertSame(0, $exitCode);
    }

    public function test_rejects_invalid_strip_components(): void
    {
        $driver = $this->createMock(RepositoryAwareArchiveDriver::class);
        $driver->expects($this->never())->method('extractArchive');

        $tester = new CommandTester(new BorgExtractCommand(
            new ArchiveApplication(new ArchiveService($driver))
        ));

        $exitCode = $tester->execute(['--strip-components' => '-2']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('--strip-components', $tester->getDisplay());
    }

    public function test_extracts_archive_from_optional_repository(): void
    {
        $archive = new ArchiveInfo('/other/repository::backup-2026', 'borg', 'borg');
        $driver = $this->createMock(RepositoryAwareArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('listArchivesFromRepository')
            ->with('/other/repository')
            ->willReturn([$archive]);
        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, '/tmp/restored');

        $command = new BorgExtractCommand(
            new ArchiveApplication(new ArchiveService($driver))
        );
        $tester = new CommandTester($command);
        $tester->setInputs([$archive->path]);

        $exitCode = $tester->execute([
            'destination' => '/tmp/restored',
            '--repository' => '/other/repository',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Extraktion erfolgreich', $tester->getDisplay());
    }
}
