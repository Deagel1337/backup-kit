<?php

namespace DatabaseBackup\Exception;

use DatabaseBackup\Exception\InvalidDumpException;


final class DumpNotFoundException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei existiert nicht.'
    ) {
        parent::__construct($message);
    }
}