<?php

namespace Tests\Unit\Application\Archive;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArchiveApplicationPruneTest extends TestCase
{
    public function test_prune_passes_retention_policy_to_driver(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver
            ->expects($this->once())
            ->method('prune')
            ->with(3, 7, null, 12, null);

        (new ArchiveApplication(new ArchiveService($driver)))->prune(
            new RetentionPolicy(keepLast: 3, keepDaily: 7, keepMonthly: 12)
        );
    }

    public function test_prune_propagates_driver_exception(): void
    {
        $driver = $this->createStub(ArchiveDriver::class);
        $driver->method('prune')->willThrowException(new RuntimeException('Pruning failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Pruning failed');

        (new ArchiveApplication(new ArchiveService($driver)))->prune(new RetentionPolicy(keepLast: 1));
    }
}
