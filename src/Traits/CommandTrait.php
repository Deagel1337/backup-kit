<?php

namespace Src\Traits;

use Process\ProcessRunner\ProcessRunner;

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