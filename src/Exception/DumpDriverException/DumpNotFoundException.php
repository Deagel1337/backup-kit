<?php

namespace Backup\Php\Exception\DumpDriverException;

use Backup\Php\Exception\DumpDriverException\InvalidDumpException;

final class DumpNotFoundException extends InvalidDumpException
{
    public function __construct(
        string $message = 'Die Dump-Datei existiert nicht.'
    ) {
        parent::__construct($message);
    }
}