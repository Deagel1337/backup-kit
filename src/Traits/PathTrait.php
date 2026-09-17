<?php

namespace Src\Traits;

trait PathTrait
{
    private function temporaryPath(string $name): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($name);
    }

    private function doesPathExist(string $path): bool
    {
       return file_exists($path);
    }
}