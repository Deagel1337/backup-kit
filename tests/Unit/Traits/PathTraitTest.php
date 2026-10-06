<?php

namespace Tests\Unit\Traits;

use Deagel1337\Backup\Kit\Traits\PathTrait;
use PHPUnit\Framework\TestCase;

final class PathTraitTest extends TestCase
{
    public function test_temporary_path_uses_system_temporary_directory(): void
    {
        $testClass = new class
        {
            use PathTrait;

            public function getTemporaryPath(string $name): string
            {
                return $this->temporaryPath($name);
            }
        };

        $result = $testClass->getTemporaryPath('backup.zip');

        $this->assertSame(
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup.zip',
            $result
        );
    }

    public function test_temporary_path_uses_only_basename(): void
    {
        $testClass = new class
        {
            use PathTrait;

            public function getTemporaryPath(string $name): string
            {
                return $this->temporaryPath($name);
            }
        };

        $result = $testClass->getTemporaryPath(
            '/some/other/path/backup.zip'
        );

        $this->assertSame(
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup.zip',
            $result
        );
    }

    public function test_does_path_exist_returns_true_for_existing_file(): void
    {
        $testClass = new class
        {
            use PathTrait;

            public function pathExists(string $path): bool
            {
                return $this->doesPathExist($path);
            }
        };

        $file = tempnam(sys_get_temp_dir(), 'path_trait_test_');

        $this->assertNotFalse($file);

        try {
            $this->assertTrue(
                $testClass->pathExists($file)
            );
        } finally {
            unlink($file);
        }
    }

    public function test_does_path_exist_returns_false_for_non_existing_path(): void
    {
        $testClass = new class
        {
            use PathTrait;

            public function pathExists(string $path): bool
            {
                return $this->doesPathExist($path);
            }
        };

        $path = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'this_file_should_not_exist_'.uniqid();

        $this->assertFalse(
            $testClass->pathExists($path)
        );
    }
}
