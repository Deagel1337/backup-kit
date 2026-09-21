<?php

namespace Backup\Php\Application\Restore;

use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Backup\Php\Services\Interface\RestoreServiceInterface;
use Backup\Php\Services\RestoreService;
use Backup\Php\Step\Restore\Rollback\MariaDbRestoreRollbackHandler;
use Backup\Php\Step\Runner\StepRunner;
use Backup\Php\Reporter\ConsoleProgressReporter;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Archive\Model\ArchiveInfo;

final class RestoreMariaDbApplication
{
    public function __construct(
        private readonly RestoreServiceInterface $service,
    ) {}

    public function run(DatabaseDump $dump): void {
        $context = new RestoreContext(
            archive: new ArchiveInfo('', '', ''),
            dump: $dump,
            destination: '',
        );

        $this->service->restore($context);
    }

    public static function create(DatabaseBackupDriver $driver, array $steps): self
    {
        $reporter = new ConsoleProgressReporter();

        $runner = new StepRunner($reporter);

        $rollbackHandler = new MariaDbRestoreRollbackHandler($driver);
        

        $service = new RestoreService(
            steps: $steps,
            runner: $runner,
            rollback: $rollbackHandler
        );

        return new self(
            service: $service,
        );
    }
}