<?php

namespace Tests\Unit\Application;

use Backup\Php\Application\BackupMariaDbApplication;
use Backup\Php\Context\BackupContext;
use Backup\Php\Services\Interface\BackupServiceInterface;
use PHPUnit\Framework\TestCase;

final class BackupMariaDbApplicationTest extends TestCase
{
    public function testRunsBackup(): void
    {
        $service = $this->createMock(BackupServiceInterface::class);

        $context = new BackupContext(
            destination: '/tmp/backup.sql'
        );

        $service
            ->expects($this->once())
            ->method('backup')
            ->with($context);

        $application = new BackupMariaDbApplication($service);

        $application->run('/tmp/backup.sql');
    }
}