<?php

namespace Tests\Unit\Traits;

use Deagel1337\Backup\Kit\Traits\HumanReadableTrait;

final class HumanReadableTestClass
{
    use HumanReadableTrait;

    /**
     * Formats bytes to a string representation.
     */
    public function format(float $bytes, int $precision = 2): string
    {
        return $this->formatBytes($bytes, $precision);
    }
}
