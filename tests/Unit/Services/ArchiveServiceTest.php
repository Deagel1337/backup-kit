<?php

namespace Tests\Unit\Services;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Interfaces\RepositoryAwareArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArchiveServiceTest extends TestCase
{
    public function test_constructor_does_not_validate_original_repository(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->never())->method('validateRequirements');

        new ArchiveService($driver);

        $this->addToAssertionCount(1);
    }

    public function test_list_archives_validates_repository_override_before_delegating(): void
    {
        $driver = $this->createMock(RepositoryAwareArchiveDriver::class);
        $archives = [new ArchiveInfo('/other/repository::backup', 'borg', 'borg')];

        $driver
            ->expects($this->never())
            ->method('validateRequirements');
        $driver
            ->expects($this->once())
            ->method('validateRepositoryRequirements');
        $driver
            ->expects($this->once())
            ->method('listArchivesFromRepository')
            ->with('/other/repository')
            ->willReturn($archives);

        $result = (new ArchiveService($driver))->listArchives('/other/repository');

        $this->assertSame($archives, iterator_to_array($result));
    }

    public function test_create_archive_delegates_to_driver(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $paths = [
            '/tmp/database.sql',
            '/tmp/config',
        ];

        $archive = new ArchiveInfo(
            '/tmp/backup.tar.gz',
            'tar',
            'tar.gz'
        );

        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->with(
                $paths,
                'backup.tar.gz'
            )
            ->willReturn($archive);

        $service = new ArchiveService($driver);

        $result = $service->createArchive(
            $paths,
            'backup.tar.gz'
        );

        $this->assertSame(
            $archive,
            $result
        );
    }

    public function test_create_archive_propagates_driver_exception(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('createArchive')
            ->with(
                ['/tmp/database.sql'],
                'backup.tar.gz'
            )
            ->willThrowException(
                new RuntimeException('Archive failed')
            );

        $service = new ArchiveService($driver);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Archive failed');

        $service->createArchive(
            ['/tmp/database.sql'],
            'backup.tar.gz'
        );
    }

    public function test_extract_archive_delegates_to_driver(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $archive = new ArchiveInfo(
            '/tmp/backup.tar.gz',
            'tar',
            'tar.gz'
        );

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with(
                $archive,
                '/tmp/restore'
            );

        $service = new ArchiveService($driver);

        $service->extractArchive(
            $archive,
            '/tmp/restore'
        );
    }

    public function test_extract_archive_passes_paths_and_strip_components_to_driver(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $archive = new ArchiveInfo('repo::backup', 'borg', 'borg');

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, '/tmp/restore', ['var/www'], 3);

        (new ArchiveService($driver))->extractArchive($archive, '/tmp/restore', ['var/www'], 3);
    }

    public function test_extract_archive_rejects_negative_strip_components(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->never())->method('validateArchive');
        $driver->expects($this->never())->method('extractArchive');

        $archive = new ArchiveInfo('repo::backup', 'borg', 'borg');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('strip-components darf nicht negativ sein.');

        (new ArchiveService($driver))->extractArchive(
            $archive,
            '/tmp/restore',
            [],
            -1
        );
    }

    public function test_extract_archive_propagates_driver_exception(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $archive = new ArchiveInfo(
            '/tmp/backup.tar.gz',
            'tar',
            'tar.gz'
        );

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with(
                $archive,
                '/tmp/restore'
            )
            ->willThrowException(
                new RuntimeException('Extraction failed')
            );

        $service = new ArchiveService($driver);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Extraction failed');

        $service->extractArchive(
            $archive,
            '/tmp/restore'
        );
    }

    public function test_prune_delegates_to_driver(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver
            ->expects($this->once())
            ->method('prune')
            ->with(5, 7, 4, 12, 3);

        (new ArchiveService($driver))->prune(
            keepLast: 5,
            keepDaily: 7,
            keepWeekly: 4,
            keepMonthly: 12,
            keepYearly: 3,
        );
    }

    public function test_list_archives_delegates_repository_override_to_aware_driver(): void
    {
        $driver = $this->createMock(RepositoryAwareArchiveDriver::class);
        $archives = [
            new ArchiveInfo('/other/repository::backup', 'borg', 'borg'),
        ];

        $driver
            ->expects($this->once())
            ->method('listArchivesFromRepository')
            ->with('/other/repository')
            ->willReturn($archives);

        $result = (new ArchiveService($driver))->listArchives('/other/repository');

        $this->assertSame($archives, iterator_to_array($result));
    }

    public function test_list_archives_rejects_repository_override_for_unaware_driver(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver->expects($this->never())->method('listArchives');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Der Archivtreiber unterstützt keine Repository-Auswahl.');

        (new ArchiveService($driver))->listArchives('/other/repository');
    }

    public function test_prune_propagates_driver_exception(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver
            ->expects($this->once())
            ->method('prune')
            ->with(5, null, null, null, null)
            ->willThrowException(new RuntimeException('Pruning failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pruning failed');

        (new ArchiveService($driver))->prune(keepLast: 5);
    }
}
