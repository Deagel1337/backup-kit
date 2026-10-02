<?php

namespace Deagel1337\Backup\Kit\Step\Interface;

use Deagel1337\Backup\Kit\Context\BackupContext;

interface BackupStep extends ProgressStep
{
    public function execute(BackupContext $context): void;
}
