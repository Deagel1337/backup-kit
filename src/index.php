<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DatabaseBackup\Driver\MariaDbDriver\MariaDbBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Service\DatabaseBackupService\DatabaseBackupService;

$connection = new DatabaseConnection(
    driver: 'mariadb',
    host: 'localhost',
    port: 6033,
    database: 'dev-db',
    username: "devuser",
    password: "devpass"
);

$driver = new MariaDbBackupDriver($connection);

$service = DatabaseBackupService::getInstance($driver);

$dump = $service->createDump("backup.sql");

echo $dump->path;