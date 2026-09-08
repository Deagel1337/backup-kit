<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Archive\Service\ArchiveService;
use Archive\Driver\BorgArchiveDriver;
use DatabaseBackup\Driver\MariaDbDriver\MariaDbBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Service\DatabaseBackupService\DatabaseBackupService;


try {
    $connection = new DatabaseConnection(
        driver: 'mariadb',
        host: 'localhost',
        port: 6033,
        database: 'dev-db',
        username: "devuser",
        password: "devpass"
    );

    // Datenbank-Dump wird erstellt
    $driver = new MariaDbBackupDriver($connection);
    $service = DatabaseBackupService::getInstance($driver);
    $dump = $service->createDump("backup.sql");

    echo $dump->path;

    // Archive wird erstellt
    $borgRepository = getenv('BORG_REPOSITORY');
    if ($borgRepository === false || trim($borgRepository) === '') {
        throw new RuntimeException('Die Umgebungsvariable BORG_REPOSITORY ist nicht gesetzt.');
    }

    $archiveDriver = new BorgArchiveDriver(
        $borgRepository,
        getenv('BORG_PASSPHRASE') ?: '',
        getenv('BORG_SSH_KEY_PATH') ?: null,
        ($port = getenv('BORG_SSH_PORT')) !== false ? (int) $port : null,
    );
    $archiveService = new ArchiveService($archiveDriver);
    $archive = $archiveService->createArchive([$dump->path], 'backup-' . date('Ymd-His'));
    echo $archive->path;
} catch (Exception $e) {
    echo $e->getMessage();
} 
