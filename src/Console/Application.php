<?php

declare(strict_types=1);

namespace Backup\Php\Console;

use Backup\Php\Application\Archive\ArchiveApplication;
use Backup\Php\Archive\Model\ArchiveInfo;
use Backup\Php\Console\BackupBorgCommand;
use Backup\Php\Console\BorgRestoreCommand;
use Backup\Php\Console\DumpDatabaseCommand;
use Backup\Php\Console\RestoreDatabaseCommand;
use Backup\Php\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct(
        ArchiveApplication $archive,
        DatabaseBackupDriver $driver,
        ArchiveInfo $repository
    )
    {
        parent::__construct(
            name: 'restore, backup and restore',
            version: '1.0.0',
        );

        $this->addCommand(
            new BorgRestoreCommand($archive, $repository)
        );

        $this->addCommand(
            new DumpDatabaseCommand($driver)
        );

        $this->addCommand(
            new RestoreDatabaseCommand($driver)
        );

        $this->addCommand(
            new BackupBorgCommand($archive)
        );
    }
}