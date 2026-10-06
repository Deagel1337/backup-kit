<?php

namespace Deagel1337\Backup\Kit\Traits;

trait HumanReadableTrait
{
    public function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = (int) min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return sprintf('%s %s', round($bytes, $precision), $units[$pow]);
    }
}
