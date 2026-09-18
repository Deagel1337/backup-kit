<?php

namespace Tests\Unit\Traits;

use Backup\Php\Traits\PathTrait;
use PHPUnit\Framework\TestCase;

final class PathTraitTest extends TestCase
{
    public function testTemporaryPathUsesSystemTemporaryDirectory(): void
    {
        $testClass = new class {
            use PathTrait;

            public function getTemporaryPath(string $name): string
            {
                return $this->temporaryPath($name);
            }
        };

        $result = $testClass->getTemporaryPath('backup.zip');

        $this->assertSame(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup.zip',
            $result
        );
    }

    public function testTemporaryPathUsesOnlyBasename(): void
    {
        $testClass = new class {
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
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'backup.zip',
            $result
        );
    }

    public function testDoesPathExistReturnsTrueForExistingFile(): void
    {
        $testClass = new class {
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

    public function testDoesPathExistReturnsFalseForNonExistingPath(): void
    {
        $testClass = new class {
            use PathTrait;

            public function pathExists(string $path): bool
            {
                return $this->doesPathExist($path);
            }
        };

        $path = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'this_file_should_not_exist_' . uniqid();

        $this->assertFalse(
            $testClass->pathExists($path)
        );
    }
}
