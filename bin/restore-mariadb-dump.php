<?php

use Deagel1337\Backup\Kit\Application\Restore\RestoreMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Driver\MariaDbBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDatabaseStep;

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