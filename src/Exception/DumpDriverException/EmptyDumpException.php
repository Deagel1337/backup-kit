<?php

namespace DatabaseBackup\Exception;

use DatabaseBackup\Exception\InvalidDumpException;


final class EmptyDumpException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei ist leer.'
    ) {
        parent::__construct($message);
    }
}