<?php

namespace Deagel1337\Backup\Kit\Exception\DumpDriverException;

final class InvalidDumpDriverException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Der Dump gehört nicht zum Datenbank-Treiber.'
    ) {
        parent::__construct($message);
    }
}
