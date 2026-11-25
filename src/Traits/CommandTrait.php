<?php

namespace Deagel1337\Backup\Kit\Traits;

use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;

trait CommandTrait
{
    abstract protected function processRunner(): ProcessRunner;

    abstract protected function allowedCommands(): array;

    public function isCommandAvailable(string $command): bool
    {
        if(! $this->isCommandAllowed($command)) {
            return false;
        }
        
        $result = $this->processRunner()->run([
            $command,
            '--version',
        ]);

        return $result->exitCode === 0;
    }

    public function isCommandAllowed(string $command): bool
    {
        return in_array($command, $this->allowedCommands(), true);
    }
}
