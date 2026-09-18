<?php

namespace Backup\Php\Step\Interface;

use Backup\Php\Context\BackupContext;
use Backup\Php\Step\Interface\ProgressStep;

interface BackupStep extends ProgressStep
{
    public function execute(BackupContext $context): void;
}