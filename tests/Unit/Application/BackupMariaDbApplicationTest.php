<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use Backup\Php\Application\BackupMariaDbApplication;
use Backup\Php\Context\BackupContext;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Services\Interface\BackupServiceInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupMariaDbApplicationTest extends TestCase
{
    public function testRunsBackupAndReturnsDump(): void
    {
        $dump = new DatabaseDump(
            path: '/tmp/backup.sql',
            driver: 'mariadb',
            format: 'sql',
        );

        $service = $this->createMock(BackupServiceInterface::class);

        $service
            ->expects($this->once())
            ->method('backup')
            ->with(
                $this->callback(
                    static function (BackupContext $context) use ($dump): bool {
                        $context->dump = $dump;

                        return $context->destination === '/tmp/backup.sql';
                    }
                )
            );

        $application = new BackupMariaDbApplication($service);

        $result = $application->run('/tmp/backup.sql');

        self::assertSame($dump, $result);
    }

    public function testThrowsExceptionWhenBackupDoesNotCreateDump(): void
    {
        $service = $this->createMock(BackupServiceInterface::class);

        $service
            ->expects($this->once())
            ->method('backup')
            ->with(
                $this->callback(
                    static fn (BackupContext $context): bool =>
                        $context->destination === '/tmp/backup.sql'
                )
            );

        $application = new BackupMariaDbApplication($service);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Der Backup-Prozess hat keinen Datenbank-Dump erzeugt'
        );

        $application->run('/tmp/backup.sql');
    }
}