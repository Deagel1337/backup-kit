<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Driver\MariaDbDriver\MariaDbBackupDriver;
use Restore\Reporter\ConsoleProgressReporter;
use Restore\Context\BackupContext;
use Restore\Step\BackupDatabaseStep;
use Src\Services\BackupService;

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

    $backupDestination = $argv[1] ?? 'backup.sql';

    $backupContext = new BackupContext(
        destination: $backupDestination
    );

    $backupService = new BackupService(
        steps: [
            new BackupDatabaseStep($driver)
        ], 
        progress: $progress
    );

    $backupService->backup($backupContext);
} catch(\Throwable $e) {
    throw $e;
}