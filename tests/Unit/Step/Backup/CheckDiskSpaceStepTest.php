<?php

namespace Tests\Unit\Step\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Backup\CheckDiskSpaceStep;
use PHPUnit\Framework\TestCase;

final class CheckDiskSpaceStepTest extends TestCase
{
    public function test_returns_correct_name(): void
    {
        $step = new CheckDiskSpaceStep;

        $this->assertSame(
            'Backup-Verzeichnis erstellen',
            $step->name()
        );
    }

    public function test_outputs_free_space(): void
    {
        $step = new CheckDiskSpaceStep('/');
        $context = new BackupContext;

        $freeSpace = disk_free_space('/');

        $this->assertNotFalse($freeSpace);

        $this->expectOutputString(
            'Free space: '
            .$this->formatBytes($freeSpace)
            .' bytes'
        );

        $step->execute($context);
    }

    public function test_uses_configured_path(): void
    {
        $path = sys_get_temp_dir();

        $step = new CheckDiskSpaceStep($path);
        $context = new BackupContext;

        $freeSpace = disk_free_space($path);

        $this->assertNotFalse($freeSpace);

        $this->expectOutputString(
            'Free space: '
            .$this->formatBytes($freeSpace)
            .' bytes'
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

        $pow = (int) min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return sprintf('%s', round($bytes, 2).' '.$units[$pow]);
    }
}
