<?php

namespace Deagel1337\Backup\Kit\Exception\DumpDriverException;

use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpException;

final class DumpNotFoundException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei existiert nicht.'
    ) {
        parent::__construct($message);
    }
}