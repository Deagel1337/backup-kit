<?php

namespace Backup\Php\Exception\DumpDriverException;

use Backup\Php\Exception\DumpDriverException\InvalidDumpException;



final class EmptyDumpException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei ist leer.'
    ) {
        parent::__construct($message);
    }
}