<?php

namespace DatabaseBackup\Exception;

use DatabaseBackup\Exception\InvalidDumpException;


final class InvalidDumpDriverException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Der Dump gehört nicht zum Datenbank-Treiber.'
    ) {
        parent::__construct($message);
    }
}