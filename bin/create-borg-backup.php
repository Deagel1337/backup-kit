<?php

use Backup\Php\Application\Backup\CreateBorgBackupApplication;
use Backup\Php\Archive\Driver\BorgArchiveDriver;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../src');
$dotenv->load();

$archiveName = $argv[1] ?? "Borg Backup";

$driver = new BorgArchiveDriver(
    repository: $_ENV['BORG_REPOSITORY'] ?? '',
    passphrase: $_ENV['BORG_PASSPHRASE'] ?? '',
    sshPort: (int) $_ENV['BORG_SSH_PORT'] ?? 22
);

$paths = [
    './src/TestFiles/test1.txt',
    './src/TestFiles/test2.txt',
    './src/TestFiles/test3.txt',
];

$app = new CreateBorgBackupApplication($driver);

$createdArchive = $app->run($paths, $archiveName);

$app->listBorgArchiveContent($createdArchive);