<?php

namespace Backup\Php\Traits;

use Backup\php\Process\Interface\ProcessRunner;

trait CommandTrait
{
    abstract protected function processRunner(): ProcessRunner;


    public function isCommandAvailable(string $command): bool
    {
        $result = $this->processRunner()->run([
            $command,
            '--version',
        ]);

        return $result->exitCode === 0;
    }
}