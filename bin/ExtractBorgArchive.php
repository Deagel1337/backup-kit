<?php

use Backup\Php\Archive\Driver\BorgArchiveDriver;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Services\ArchiveService;
use Dotenv\Dotenv;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    $archiveName = $argv[1];
    $destination = $argv[2] ?? __DIR__ . '/../backups';

    $repository = $_ENV['BORG_REPOSITORY'] ?? '';

    $driver = new BorgArchiveDriver(
        repository: $repository,
        passphrase: $_ENV['BORG_PASSPHRASE'] ?? '',
        sshPort: $_ENV['BORG_SSH_PORT'] ?? 22
    );

    $archive = new ArchiveService($driver);
    $fullArchivePath = $repository . '::' . $archiveName;
    $archiveToRestore = new ArchiveInfo($fullArchivePath, 'borg', 'sql');
    $archive->extractArchive($archiveToRestore, $destination);
} catch (\Throwable $e) {
    throw $e;
}