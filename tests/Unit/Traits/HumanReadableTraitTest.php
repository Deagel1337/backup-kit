<?php

namespace Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;

final class HumanReadableTraitTest extends TestCase
{
    /**
     * Summary of testClass
     */
    private HumanReadableTestClass $testClass;

    protected function setUp(): void
    {
        $this->testClass = new HumanReadableTestClass;
    }

    public function test_formats_bytes(): void
    {
        $this->assertSame(
            '500 B',
            $this->testClass->format(500)
        );
    }

    public function test_formats_kilobytes(): void
    {
        $this->assertSame(
            '1 KB',
            $this->testClass->format(1024)
        );
    }

    public function test_formats_megabytes(): void
    {
        $this->assertSame(
            '1 MB',
            $this->testClass->format(1024 * 1024)
        );
    }

    public function test_formats_gigabytes(): void
    {
        $this->assertSame(
            '1 GB',
            $this->testClass->format(1024 * 1024 * 1024)
        );
    }

    public function test_formats_terabytes(): void
    {
        $this->assertSame(
            '1 TB',
            $this->testClass->format(1024 ** 4)
        );
    }

    public function test_zero_bytes(): void
    {
        $this->assertSame(
            '0 B',
            $this->testClass->format(0)
        );
    }

    public function test_negative_bytes_are_treated_as_zero(): void
    {
        $this->assertSame(
            '0 B',
            $this->testClass->format(-100)
        );
    }

    public function test_uses_given_precision(): void
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

    public function test_large_values_are_limited_to_terabytes(): void
    {
        $this->assertSame(
            '1024 TB',
            $this->testClass->format(1024 ** 5)
        );
    }
}
