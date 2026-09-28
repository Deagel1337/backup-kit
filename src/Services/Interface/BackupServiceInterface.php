<?php

namespace Deagel1337\Backup\Kit\Services\Interface;

use Deagel1337\Backup\Kit\Context\BackupContext;

interface BackupServiceInterface
{
    public function backup(BackupContext $context): void;
}