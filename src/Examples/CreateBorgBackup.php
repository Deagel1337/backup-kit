<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Archive\Driver\BorgArchiveDriver;
use Archive\Service\ArchiveService;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $archiveName = $argv[1] ?? "Borg Backup";

    $driver = new BorgArchiveDriver(
        repository: $_ENV['BORG_REPOSITORY'] ?? '',
        passphrase: $_ENV['BORG_PASSPHRASE'] ?? '',
        sshPort: $_ENV['BORG_SSH_PORT'] ?? 22
    );

    $archive = new ArchiveService($driver) ;

    $paths = [
        './src/TestFiles/test1.txt',
        './src/TestFiles/test2.txt',
        './src/TestFiles/test3.txt',
    ];

    $archiveInfo = $archive->createArchive($paths, $archiveName);

    $driver->listRepositoryBackups();
} catch(\Throwable $e) {
    throw $e;
}