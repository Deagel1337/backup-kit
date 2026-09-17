<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DatabaseBackup\Driver\MariaDbDriver\MariaDbBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use Archive\Model\ArchiveInfo;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use Restore\Model\RestoreContext;
use Restore\Reporter\ConsoleProgressReporter;
use Restore\Step\RestoreDatabaseStep;
use Src\Services\RestoreService;

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

    $driver = new MariaDbBackupDriver($connection);

    $restorePath = $argv[1] ?? 'backup.sql';
    $restoreDump = new DatabaseDump($restorePath, $connection->driver, "sql");

    $restoreService = new RestoreService(
        steps: [
            new RestoreDatabaseStep($driver),
        ],
        progress: $progress
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