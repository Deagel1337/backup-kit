<?php

namespace Deagel1337\Backup\Kit\Step\Interface;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\Step\Interface\ProgressStep;

interface RestoreStep extends ProgressStep
{
    public function execute(RestoreContext $context): void;
}