<?php

namespace Deagel1337\Backup\Kit\Reporter\Interface;

interface ProcessReporter
{
    /**
     * Summary of command
     *
     * @param  array<string>  $command
     */
    public function command(array $command): void;
}
