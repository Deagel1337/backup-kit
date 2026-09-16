<?php

namespace Restore\Interfaces;

use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use Restore\Context\BackupContext;
use Restore\Interfaces\ProgressStep;

interface BackupStep extends ProgressStep
{
    public function execute(BackupContext $context): void;
}