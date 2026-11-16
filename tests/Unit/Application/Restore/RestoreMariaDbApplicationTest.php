<?php

namespace Tests\Unit\Application\Restore;

use Deagel1337\Backup\Kit\Application\Restore\RestoreMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Interfaces\DatabaseBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use PHPUnit\Framework\TestCase;

final class RestoreMariaDbApplicationTest extends TestCase
{
    public function test_create_uses_the_injected_progress_reporter(): void
    {
        $reporter = $this->createMock(ProgressReporter::class);
        $reporter->expects($this->once())->method('started')->with(0);
        $reporter->expects($this->never())->method('stepStarted');
        $reporter->expects($this->never())->method('stepFinished');
        $reporter->expects($this->never())->method('stepFailed');
        $reporter->expects($this->once())->method('finished');

        $application = RestoreMariaDbApplication::create(
            $this->createStub(DatabaseBackupDriver::class),
            [],
            $reporter,
        );

        $application->run(new DatabaseDump('/tmp/backup.sql', 'mariadb', 'sql'));
    }
}
