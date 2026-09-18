<?php

namespace Backup\Php\Step\Interface;

use Backup\Php\Context\RestoreContext;
use Backup\Php\Step\Interface\ProgressStep;

interface RestoreStep extends ProgressStep
{
    public function execute(RestoreContext $context): void;
}