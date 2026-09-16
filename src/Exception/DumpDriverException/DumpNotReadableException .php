<?php

namespace DatabaseBackup\Exception;

use DatabaseBackup\Exception\InvalidDumpException;


final class DumpNotReadableException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei ist nicht lesbar.'
    ) {
        parent::__construct($message);
    }
}