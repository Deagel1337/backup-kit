<?php

namespace Tests\Unit\Step\Restore;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\ValidateArchiveStep;
use PHPUnit\Framework\TestCase;
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