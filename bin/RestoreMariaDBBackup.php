<?php

use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Context\RestoreContext;
use Backup\Php\DatabaseBackup\Driver\MariaDbBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Reporter\ConsoleProgressReporter;
use Backup\Php\Services\RestoreService;
use Backup\Php\Step\Restore\RestoreDatabaseStep;
use Backup\Php\Step\Runner\StepRunner;

require_once __DIR__ . '/../vendor/autoload.php';

try
{
    $connection = new DatabaseConnection(
        driver: 'mariadb',
        host: 'localhost',
        port: 6033,
        database: 'dev-db',
        username: 'devuser',
        password: 'devpass'
    );

    $progress = new ConsoleProgressReporter();
    $runner = new StepRunner($progress);
    $driver = new MariaDbBackupDriver($connection);

    $restorePath = $argv[1] ?? 'backup.sql';
    $restoreDump = new DatabaseDump($restorePath, $connection->driver, "sql");

    $restoreService = new RestoreService(
        steps: [
            new RestoreDatabaseStep($driver),
        ],
        runner: $runner,
    );
    

    $restoreService->restore(
        new RestoreContext(
            archive: new ArchiveInfo('', '', ''),
            dump: $restoreDump,
            destination: '',
        )
    );
} catch(\Throwable $e) {
    throw $e;
}