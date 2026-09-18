<?php

namespace Tests\Unit\Step\Backup;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Backup\CheckDiskSpaceStep;
use PHPUnit\Framework\TestCase;

final class CheckDiskSpaceStepTest extends TestCase
{
    public function testReturnsCorrectName(): void
    {
        $step = new CheckDiskSpaceStep();

        $this->assertSame(
            'Backup-Verzeichnis erstellen',
            $step->name()
        );
    }

    public function testOutputsFreeSpace(): void
    {
        $step = new CheckDiskSpaceStep('/');
        $context = new BackupContext();

        $freeSpace = diskfreespace('/');

        $this->assertNotFalse($freeSpace);

        $this->expectOutputString(
            'Free space: '
            . $this->formatBytes($freeSpace)
            . ' bytes'
        );

        $step->execute($context);
    }

    public function testUsesConfiguredPath(): void
    {
        $path = sys_get_temp_dir();

        $step = new CheckDiskSpaceStep($path);
        $context = new BackupContext();

        $freeSpace = diskfreespace($path);

        $this->assertNotFalse($freeSpace);

        $this->expectOutputString(
            'Free space: '
            . $this->formatBytes($freeSpace)
            . ' bytes'
        );

        $step->execute($context);
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);

        $pow = floor(
            ($bytes ? log($bytes) : 0) / log(1024)
        );

        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
