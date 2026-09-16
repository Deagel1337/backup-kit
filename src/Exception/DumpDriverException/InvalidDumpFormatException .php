<?php

namespace DatabaseBackup\Exception;

use DatabaseBackup\Exception\InvalidDumpException;

final class InvalidDumpFormatException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Der Dump muss im SQL-Format vorliegen.'
    ) {
        parent::__construct($message);
    }
}