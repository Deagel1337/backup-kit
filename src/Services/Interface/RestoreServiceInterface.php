<?php

namespace Backup\Php\Services\Interface;

use Backup\Php\Context\RestoreContext;

interface RestoreServiceInterface
{
    public function restore(RestoreContext $context): void;
}
