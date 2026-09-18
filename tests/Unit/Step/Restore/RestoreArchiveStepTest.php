<?php

namespace Tests\Unit\Step\Restore;

use Backup\Php\Archive\Interfaces\ArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Step\Restore\RestoreArchiveStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestoreArchiveStepTest extends TestCase
{
    public function testExtractsContextArchiveToDestination(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $archive = new ArchiveInfo(
            '/tmp/backup.tar.gz',
            'tar',
            'tar.gz'
        );

        $context = new RestoreContext(
            $archive,
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($archive, '/tmp/restore');

        (new RestoreArchiveStep($driver))->execute($context);
    }

    public function testExtractExceptionIsPropagated(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('extractArchive')
            ->with($this->isInstanceOf(ArchiveInfo::class), '/tmp/restore')
            ->willThrowException(
                new RuntimeException('extract failed')
            );

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('extract failed');

        (new RestoreArchiveStep($driver))->execute($context);
    }

    public function testHasExpectedName(): void
    {
        $this->assertSame(
            'Archiv wiederherstellen',
            (new RestoreArchiveStep(
                $this->createMock(ArchiveDriver::class)
            ))->name()
        );
    }
}