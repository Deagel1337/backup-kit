<?php

namespace Backup\Php\Reporter\Interface;

interface ProcessReporter
{
    public function command(array $command): void;
}