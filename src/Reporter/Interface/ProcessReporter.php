<?php

namespace Deagel1337\Backup\Kit\Reporter\Interface;

interface ProcessReporter
{
    public function command(array $command): void;
}