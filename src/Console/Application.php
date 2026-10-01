<?php

declare(strict_types=1);

namespace Deagel1337\Backup\Kit\Console;

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Model\ArchiveInfo;
use Deagel1337\Backup\Kit\Console\BackupBorgCommand;
use Deagel1337\Backup\Kit\Console\BorgListCommand;
use Deagel1337\Backup\Kit\Console\BorgRestoreCommand;
use Deagel1337\Backup\Kit\Console\DumpDatabaseCommand;
use Deagel1337\Backup\Kit\Console\RestoreDatabaseCommand;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct(
        ArchiveApplication $archive,
        DatabaseBackupDriver $driver,
    )
    {
        parent::__construct(
            name: 'restore, backup and restore',
            version: '1.0.0',
        );

        $this->addCommand(
            new BorgRestoreCommand($archive)
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

        $this->addCommand(
            new BorgListCommand($archive)
        );
    }
}