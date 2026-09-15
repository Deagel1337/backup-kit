<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DatabaseBackup\Driver\MariaDbDriver\MariaDbBackupDriver;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use Archive\Model\ArchiveInfo;
use Restore\Model\RestoreContext;
use Restore\Step\BackupDatabaseStep;
use Restore\Step\RestoreDatabaseStep;
use Src\Services\RestoreService;


try {
    $connection = new DatabaseConnection(
        driver: 'mariadb',
        host: 'localhost',
        port: 6033,
        database: 'dev-db',
        username: "devuser",
        password: "devpass"
    );

    $driver = new MariaDbBackupDriver($connection);
    $backupDestination = $argv[1] ?? 'backup.sql';
    $backupStep = new BackupDatabaseStep($driver);
    $dump = $backupStep->execute($backupDestination);

    $restoreService = new RestoreService([
        new RestoreDatabaseStep($driver),
    ]);

    $restoreService->restore(new RestoreContext(
        archive: new ArchiveInfo('', '', ''),
        dump: $dump,
        destination: '',
    ));

    echo "Backup erstellt und Datenbank wiederhergestellt: {$dump->path}" . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
