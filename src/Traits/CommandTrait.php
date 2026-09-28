<?php

namespace Deagel1337\Backup\Kit\Traits;

use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;

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