<?php

namespace Restore\Interfaces;

use Restore\Model\RestoreContext;

interface RestoreStep
{
    public function execute(RestoreContext $context): void;
}