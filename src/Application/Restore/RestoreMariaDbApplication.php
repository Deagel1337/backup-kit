<?php

namespace Deagel1337\Backup\Kit\Application\Restore;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\Services\Interface\RestoreServiceInterface;
use Deagel1337\Backup\Kit\Services\RestoreService;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\MariaDbRestoreRollbackHandler;
use Deagel1337\Backup\Kit\Step\Runner\StepRunner;
use Deagel1337\Backup\Kit\Reporter\ConsoleProgressReporter;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Step\Interface\RestoreStep;
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

    /**
     * Restores the database
     * @param DatabaseBackupDriver $driver
     * @param array<RestoreStep> $steps
     * @return RestoreMariaDbApplication
     */
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