<?php

namespace Backup\Php\Application;

use Backup\Php\Context\BackupContext;
use Backup\Php\Services\Interface\BackupServiceInterface;

final class BackupMariaDbApplication
{
    public function __construct(
        private readonly BackupServiceInterface $service,
    )
    {}

    public function run(
        string $destination
    ): void {
        $context = new BackupContext(
            destination: $destination
        );

        $this->service->backup($context);
    }
}