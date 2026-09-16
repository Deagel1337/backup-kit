<?php

namespace Restore\Interfaces;

use Restore\Model\RestoreContext;
use Restore\Interfaces\ProgressStep;

interface RestoreStep extends ProgressStep
{
    public function execute(RestoreContext $context): void;
}