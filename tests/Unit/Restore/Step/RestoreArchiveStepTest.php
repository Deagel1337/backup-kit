<?php

namespace Tests\Restore\Step;

use Archive\Driver\ArchiveDriver;
use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Model\RestoreContext;
use Restore\Step\RestoreArchiveStep;
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