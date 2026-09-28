<?php

namespace Deagel1337\Backup\Kit\Exception\DumpDriverException;

use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpException;



final class DumpNotReadableException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei ist nicht lesbar.'
    ) {
        parent::__construct($message);
    }
}