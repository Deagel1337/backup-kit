<?php

namespace Deagel1337\Backup\Kit\Exception\DumpDriverException;

final class InvalidDumpFormatException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Der Dump muss im SQL-Format vorliegen.'
    ) {
        parent::__construct($message);
    }
}
