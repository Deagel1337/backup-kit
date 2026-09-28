<?php

namespace Deagel1337\Backup\Kit\Step\Interface;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\Step\Interface\ProgressStep;

interface BackupStep extends ProgressStep
{
    public function execute(BackupContext $context): void;
}