<?php

namespace Restore\Interfaces;

use DatabaseBackup\Model\DatabaseDump\DatabaseDump;

interface BackupStep
{
    public function execute(string $destination): DatabaseDump;
}