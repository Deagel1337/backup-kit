<?php

namespace Backup\Php\Application;

use Backup\Php\Context\BackupContext;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Reporter\ConsoleProgressReporter;
use Backup\Php\Services\Interface\BackupServiceInterface;
use Backup\Php\Step\Runner\StepRunner;
use Backup\Php\Services\BackupService;
use RuntimeException;

final class BackupMariaDbApplication
{
    public function __construct(
        private readonly BackupServiceInterface $service,
    )
    {}

    public function run(string $destination): DatabaseDump 
    {
        $context = new BackupContext(
            destination: $destination
        );

        $this->service->backup($context);

        if($context->dump === null) {
            throw new RuntimeException(
                'Der Backup-Prozess hat keinen Datenbank-Dump erzeugt'
            );
        }

        return $context->dump;
    }

    public static function create(array $steps): self
    {
        $reporter = new ConsoleProgressReporter();

        $runner = new StepRunner($reporter);

        $service = new BackupService(
            steps: $steps,
            runner: $runner,
        );

        return new self(service: $service);
    }
}