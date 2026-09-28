<?php

namespace Deagel1337\Backup\Kit\Services\Interface;

use Deagel1337\Backup\Kit\Context\RestoreContext;

interface RestoreServiceInterface
{
    public function restore(RestoreContext $context): void;
}
