<?php

namespace Tests\Unit\Traits;

use Deagel1337\Backup\Kit\Traits\HumanReadableTrait;
use PHPUnit\Framework\TestCase;

final class HumanReadableTraitTest extends TestCase
{
    /**
     * Summary of testClass
     */
    private HumanReadableTestClass $testClass;

    protected function setUp(): void
    {
        $this->testClass = new HumanReadableTestClass();
    }

    public function testFormatsBytes(): void
    {
        $this->assertSame(
            '500 B',
            $this->testClass->format(500)
        );
    }

    public function testFormatsKilobytes(): void
    {
        $this->assertSame(
            '1 KB',
            $this->testClass->format(1024)
        );
    }

    public function testFormatsMegabytes(): void
    {
        $this->assertSame(
            '1 MB',
            $this->testClass->format(1024 * 1024)
        );
    }

    public function testFormatsGigabytes(): void
    {
        $this->assertSame(
            '1 GB',
            $this->testClass->format(1024 * 1024 * 1024)
        );
    }

    public function testFormatsTerabytes(): void
    {
        $this->assertSame(
            '1 TB',
            $this->testClass->format(1024 ** 4)
        );
    }

    public function testZeroBytes(): void
    {
        $this->assertSame(
            '0 B',
            $this->testClass->format(0)
        );
    }

    public function testNegativeBytesAreTreatedAsZero(): void
    {
        $this->assertSame(
            '0 B',
            $this->testClass->format(-100)
        );
    }

    public function testUsesGivenPrecision(): void
    {
        $this->assertSame(
            '1.21 KB',
            $this->testClass->format(1234, 2)
        );

        $this->assertSame(
            '1.2 KB',
            $this->testClass->format(1234, 1)
        );

        $this->assertSame(
            '1 KB',
            $this->testClass->format(1234, 0)
        );
    }

    public function testLargeValuesAreLimitedToTerabytes(): void
    {
        $this->assertSame(
            '1024 TB',
            $this->testClass->format(1024 ** 5)
        );
    }
}
