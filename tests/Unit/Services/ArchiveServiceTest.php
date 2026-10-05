<?php

namespace Tests\Unit\Services;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArchiveServiceTest extends TestCase
{
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
