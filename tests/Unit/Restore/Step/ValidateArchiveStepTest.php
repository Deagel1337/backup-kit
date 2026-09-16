<?php

namespace Tests\Restore\Step;

use Archive\Driver\ArchiveDriver;
use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Restore\Model\RestoreContext;
use Restore\Step\ValidateArchiveStep;
use RuntimeException;

final class ValidateArchiveStepTest extends TestCase
{
    public function testValidatesContextArchive(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $archive = new ArchiveInfo(
            '/tmp/archive.tar.gz',
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
            ->method('validateArchive')
            ->with($archive);

        (new ValidateArchiveStep($driver))->execute($context);
    }

    public function testValidationExceptionIsPropagated(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);

        $driver
            ->expects($this->once())
            ->method('validateArchive')
            ->with($this->isInstanceOf(ArchiveInfo::class))
            ->willThrowException(
                new RuntimeException('invalid archive')
            );

        $context = new RestoreContext(
            new ArchiveInfo('/tmp/archive', 'tar', 'tar.gz'),
            new DatabaseDump('/tmp/dump', 'sqlite', 'sqlite'),
            '/tmp/restore'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid archive');

        (new ValidateArchiveStep($driver))->execute($context);
    }

    public function testHasExpectedName(): void
    {
        $step = new ValidateArchiveStep(
            $this->createMock(ArchiveDriver::class)
        );

        $this->assertSame(
            'Archiv valiederen',
            $step->name()
        );
    }
}