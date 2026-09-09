<?php

namespace Src\Traits;

trait CommandTrait
{
    public function isCommandAvailable(string $command): bool
    {
        $process = proc_open(
            [$command, '--version'],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (!is_resource($process)) {
            return false;
        }

        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($process) === 0;
    }
}