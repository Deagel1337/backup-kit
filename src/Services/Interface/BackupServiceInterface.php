<?php

namespace Backup\Php\Services\Interface;

use Backup\Php\Context\BackupContext;

interface BackupServiceInterface
{
    public function backup(BackupContext $context): void;
}