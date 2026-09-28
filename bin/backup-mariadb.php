<?php

use Deagel1337\Backup\Kit\DatabaseBackup\Driver\MariaDbBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\Step\Backup\BackupDatabaseStep;
use Deagel1337\Backup\Kit\Step\Backup\CheckDiskSpaceStep;
use Deagel1337\Backup\Kit\Step\Backup\ShowBackupContextStep;
use Deagel1337\Backup\Kit\Application\BackupMariaDbApplication;

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

$steps = [
    new CheckDiskSpaceStep(),
    new BackupDatabaseStep($driver),
    new ShowBackupContextStep(),
];

$application = BackupMariaDbApplication::create(steps: $steps);

$application->run($argv[1] ?? 'backup.sql');