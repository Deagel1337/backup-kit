<?php

namespace Deagel1337\Backup\Kit\Exception\DumpDriverException;

final class EmptyDumpException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei ist leer.'
    ) {
        parent::__construct($message);
    }
}
