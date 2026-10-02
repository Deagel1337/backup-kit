<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\RestoreArchiveStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestoreArchiveStepTest extends TestCase
{
    public function test_extracts_context_archive_to_destination(): void
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

    public function test_extract_exception_is_propagated(): void
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

    public function test_has_expected_name(): void
    {
        $this->assertSame(
            'Archiv wiederherstellen',
            (new RestoreArchiveStep(
                $this->createMock(ArchiveDriver::class)
            ))->name()
        );
    }
}
