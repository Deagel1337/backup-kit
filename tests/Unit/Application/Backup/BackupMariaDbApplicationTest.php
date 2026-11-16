<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use Deagel1337\Backup\Kit\Application\Backup\BackupMariaDbApplication;
use Deagel1337\Backup\Kit\Context\BackupContext;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Reporter\Interface\ProgressReporter;
use Deagel1337\Backup\Kit\Services\Interface\BackupServiceInterface;
use Deagel1337\Backup\Kit\Step\Interface\BackupStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BackupMariaDbApplicationTest extends TestCase
{
    public function test_runs_backup_and_returns_dump(): void
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

    public function test_throws_exception_when_backup_does_not_create_dump(): void
    {
        $service = $this->createMock(BackupServiceInterface::class);

        $service
            ->expects($this->once())
            ->method('backup')
            ->with(
                $this->callback(
                    static fn (BackupContext $context): bool => $context->destination === '/tmp/backup.sql'
                )
            );

        $application = new BackupMariaDbApplication($service);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Der Backup-Prozess hat keinen Datenbank-Dump erzeugt'
        );

        $application->run('/tmp/backup.sql');
    }

    public function test_create_uses_the_injected_progress_reporter(): void
    {
        $dump = new DatabaseDump('/tmp/backup.sql', 'mariadb', 'sql');
        $step = $this->createMock(BackupStep::class);
        $step->expects($this->once())->method('name')->willReturn('Datenbank sichern');
        $step->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static function (BackupContext $context) use ($dump): void {
                $context->dump = $dump;
            });

        $reporter = $this->createMock(ProgressReporter::class);
        $reporter->expects($this->once())->method('started')->with(1);
        $reporter->expects($this->once())->method('stepStarted')->with(1, 1, 'Datenbank sichern');
        $reporter->expects($this->once())->method('stepFinished')->with(1, 1, 'Datenbank sichern');
        $reporter->expects($this->once())->method('finished');

        $application = BackupMariaDbApplication::create([$step], $reporter);

        self::assertSame($dump, $application->run($dump->path));
    }
}
