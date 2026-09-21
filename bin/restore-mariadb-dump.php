<?php

use Backup\Php\Application\Restore\RestoreMariaDbApplication;
use Backup\Php\DatabaseBackup\Driver\MariaDbBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Step\Restore\RestoreDatabaseStep;

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

$restorePath = $argv[1] ?? 'backup.sql';
$restoreDump = new DatabaseDump($restorePath, $connection->driver, "sql");
$application = RestoreMariaDbApplication::create(
    driver: $driver,
    steps: [
    new RestoreDatabaseStep($driver),
]);

$application->run($restoreDump);