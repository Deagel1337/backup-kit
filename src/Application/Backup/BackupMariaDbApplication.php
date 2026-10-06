<?php

namespace Deagel1337\Backup\Kit\Application\Backup;

use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Reporter\ConsoleProgressReporter;
use Deagel1337\Backup\Kit\Services\BackupService;
use Deagel1337\Backup\Kit\Services\Interface\BackupServiceInterface;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use RuntimeException;

final class BackupMariaDbApplication
{
    public function __construct(
        private readonly BackupServiceInterface $service,
    ) {}

    public function run(string $destination): DatabaseDump
    {
        $context = new BackupContext(
            destination: $destination
        );

        $this->service->backup($context);

        if ($context->dump === null) {
            throw new RuntimeException(
                'Der Backup-Prozess hat keinen Datenbank-Dump erzeugt'
            );
        }

        return $context->dump;
    }

    /**
     * Creates a database dump
     *
     * @param  array<BackupStep>  $steps
     */
    public static function create(array $steps): self
    {
        $reporter = new ConsoleProgressReporter;

        $runner = new StepRunner($reporter);

        $service = new BackupService(
            steps: $steps,
            runner: $runner,
        );

        return new self(service: $service);
    }
}
