<?php

namespace Tests\Unit\Services;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use RuntimeException;


final class ArchiveServiceTest extends TestCase
{
    public function testCreateArchiveDelegatesToDriver(): void
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

    public function testCreateArchivePropagatesDriverException(): void
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

    public function testExtractArchiveDelegatesToDriver(): void
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

    public function testExtractArchivePropagatesDriverException(): void
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
}