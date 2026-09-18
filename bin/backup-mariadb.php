<?php

use Backup\Php\DatabaseBackup\Driver\MariaDbBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\Reporter\ConsoleProgressReporter;
use Backup\Php\Services\BackupService;
use Backup\Php\Step\Backup\BackupDatabaseStep;
use Backup\Php\Step\Backup\CheckDiskSpaceStep;
use Backup\Php\Step\Backup\ShowBackupContextStep;
use Backup\Php\Step\Runner\StepRunner;
use Backup\Php\Application\BackupMariaDbApplication;

require_once __DIR__ . '/../vendor/autoload.php';

$connection = new DatabaseConnection(
    driver: 'mariadb',
    host: 'localhost',
    port: 6033,
    database: 'dev-db',
    username: 'devuser',
    password: 'devpass'
);

$driver = new MariaDbBackupDriver($connection);

$progress = new ConsoleProgressReporter();

$runner = new StepRunner($progress);

$service = new BackupService(
    steps: [
        new CheckDiskSpaceStep(),
        new BackupDatabaseStep($driver),
        new ShowBackupContextStep(),
    ],
    runner: $runner,
);

$application = new BackupMariaDbApplication($service);

$application->run($argv[1] ?? 'backup.sql');