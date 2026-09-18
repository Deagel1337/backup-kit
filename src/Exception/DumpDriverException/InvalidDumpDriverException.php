<?php

namespace Backup\Php\Exception\DumpDriverException;

use Backup\Php\Exception\DumpDriverException\InvalidDumpException;



final class InvalidDumpDriverException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Der Dump gehört nicht zum Datenbank-Treiber.'
    ) {
        parent::__construct($message);
    }
}