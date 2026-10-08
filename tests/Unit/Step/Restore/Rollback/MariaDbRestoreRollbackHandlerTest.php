<?php

namespace Tests\Unit\Step\Restore\Rollback;

use Deagel1337\Backup\Kit\Context\RestoreContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\Rollback\MariaDbRestoreRollbackHandler;
use PHPUnit\Framework\TestCase;

final class MariaDbRestoreRollbackHandlerTest extends TestCase
{
    public function test_restores_rollback_dump_when_present(): void
    {
        $dump = new DatabaseDump('/tmp/rollback.sql', 'mariadb', 'sql');
        $driver = $this->createMock(DatabaseBackupDriver::class);
        $driver->expects($this->once())->method('restoreDump')->with($dump);

        (new MariaDbRestoreRollbackHandler($driver))->rollback(new RestoreContext(
            archive: null,
            dump: null,
            destination: '/tmp/restore',
            rollbackDump: $dump,
        ));
    }

    public function test_does_nothing_without_rollback_dump(): void
    {
        $driver = $this->createMock(DatabaseBackupDriver::class);
        $driver->expects($this->never())->method('restoreDump');

        (new MariaDbRestoreRollbackHandler($driver))->rollback(new RestoreContext(
            archive: null,
            dump: null,
            destination: '/tmp/restore',
        ));

        $this->addToAssertionCount(1);
    }
}
