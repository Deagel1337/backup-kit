<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Archive\Interfaces\ArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Services\ArchiveService;
use Deagel1337\Backup\Kit\Step\Backup\PruneArchivesStep;
use PHPUnit\Framework\TestCase;

final class PruneArchivesStepTest extends TestCase
{
    public function test_returns_name(): void
    {
        $step = new PruneArchivesStep(
            new ArchiveService($this->createStub(ArchiveDriver::class)),
            new RetentionPolicy(keepLast: 1),
        );

        $this->assertSame('Alte Archive aufräumen', $step->name());
    }

    public function test_prunes_with_configured_policy(): void
    {
        $driver = $this->createMock(ArchiveDriver::class);
        $driver
            ->expects($this->once())
            ->method('prune')
            ->with(null, 7, 4, null, null);

        (new PruneArchivesStep(
            new ArchiveService($driver),
            new RetentionPolicy(keepDaily: 7, keepWeekly: 4),
        ))->execute(new BackupContext);
    }
}
