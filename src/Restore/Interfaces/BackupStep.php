<?php

namespace Restore\Interfaces;

use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use Restore\Interfaces\ProgressStep;

interface BackupStep extends ProgressStep
{
    public function execute(string $destination): DatabaseDump;
}