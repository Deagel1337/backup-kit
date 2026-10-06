<?php

namespace Deagel1337\Backup\Kit\Exception;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(
        string $message = 'Die Validierung ist fehlgeschlagen'
    ) {
        parent::__construct($message);
    }
}
