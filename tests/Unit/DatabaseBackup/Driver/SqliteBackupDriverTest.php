<?php

namespace Tests\Unit\DatabaseBackup\Driver;

use Deagel1337\Backup\Kit\DatabaseBackup\Driver\SqliteBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\DumpNotFoundException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\EmptyDumpException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpDriverException;
use Deagel1337\Backup\Kit\Exception\DumpDriverException\InvalidDumpFormatException;
use Deagel1337\Backup\Kit\Process\Interface\ProcessRunner;
use Deagel1337\Backup\Kit\Process\Model\ProcessResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SqliteBackupDriverTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sqlite_test_');

        if ($path === false) {
            throw new RuntimeException(
                'Test-Dump-Datei konnte nicht erstellt werden.'
            );
        }

        $this->dumpPath = $path;

        file_put_contents(
            $this->dumpPath,
            'sqlite dump'
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }

        parent::tearDown();
    }

    public function test_accepts_readable_non_empty_sqlite_dump(): void
    {
        $dump = new DatabaseDump(
            $this->dumpPath,
            'sqlite',
            'sqlite'
        );

        $this->driver()->validateDump($dump);

        $this->assertSame('sqlite', $dump->driver);
        $this->assertSame('sqlite', $dump->format);
    }

    public function test_rejects_wrong_driver(): void
    {
        $this->expectException(InvalidDumpDriverException::class);
        $this->expectExceptionMessage(
            'Der Dump gehört nicht zum SQLite-Treiber.'
        );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'postgres',
            'sqlite'
        );

        $this->driver()->validateDump($dump);
    }

    public function test_rejects_wrong_format(): void
    {
        $this->expectException(InvalidDumpFormatException::class);
        $this->expectExceptionMessage(
            'Der Dump muss im SQL-Format vorliegen.'
        );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'sqlite',
            'sql'
        );

        $this->driver()->validateDump($dump);
    }

    public function test_rejects_missing_dump(): void
    {
        $this->expectException(DumpNotFoundException::class);
        $this->expectExceptionMessage(
            'Die Dump-Datei existiert nicht.'
        );

        $dump = new DatabaseDump(
            '/tmp/missing-sqlite-dump',
            'sqlite',
            'sqlite'
        );

        $this->driver()->validateDump($dump);
    }

    public function test_rejects_empty_dump(): void
    {
        file_put_contents($this->dumpPath, '');

        $this->expectException(EmptyDumpException::class);
        $this->expectExceptionMessage(
            'Die Dump-Datei ist leer.'
        );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'sqlite',
            'sqlite'
        );

        $this->driver()->validateDump($dump);
    }

    public function test_create_dump_uses_sqlite_backup_command(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->callback(
                    /**
                     * @param  array<int, string>  $command
                     */
                    function (array $command): bool {
                        /** @var string $backupCommand */
                        $backupCommand = $command[2];

                        return $command[0] === 'sqlite3'
                            && $command[1] === '/tmp/database.sqlite'
                            && str_starts_with($backupCommand, '.backup ');
                    }
                ),
                [],
                null,
                null,
                null
            )
            ->willReturn(
                new ProcessResult(
                    0,
                    '',
                    ''
                )
            );

        $dump = $this->driver($process)->createDump();

        $this->assertInstanceOf(
            DatabaseDump::class,
            $dump
        );

        $this->assertSame(
            'sqlite',
            $dump->driver
        );

        $this->assertSame(
            'sqlite',
            $dump->format
        );

        $this->assertFileExists($dump->path);

        if (is_file($dump->path)) {
            unlink($dump->path);
        }
    }

    public function test_create_dump_throws_when_sqlite_fails(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->willReturn(
                new ProcessResult(
                    1,
                    '',
                    'SQLite error'
                )
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Der Prozess sqlite3 konnte nicht gesichert werden.'
        );

        $this->driver($process)->createDump();
    }

    public function test_create_dump_uses_provided_backup_name(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $backupName = 'test-backup.sqlite';

        $expectedPath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .$backupName;

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'sqlite3',
                    '/tmp/database.sqlite',
                    ".backup '".$expectedPath."'",
                ],
                [],
                null,
                null,
                null
            )
            ->willReturn(
                new ProcessResult(
                    0,
                    '',
                    ''
                )
            );

        $dump = $this->driver($process)->createDump($backupName);

        $this->assertSame(
            $expectedPath,
            $dump->path
        );

        $this->assertSame(
            'sqlite',
            $dump->driver
        );

        $this->assertSame(
            'sqlite',
            $dump->format
        );

        if (is_file($dump->path)) {
            unlink($dump->path);
        }
    }

    public function test_restore_dump_copies_database_file(): void
    {
        $targetPath = tempnam(
            sys_get_temp_dir(),
            'sqlite_restore_'
        );

        if ($targetPath === false) {
            throw new RuntimeException(
                'Ziel-Datei für Restore-Test konnte nicht erstellt werden.'
            );
        }

        file_put_contents(
            $this->dumpPath,
            'original sqlite database'
        );

        $connection = new DatabaseConnection(
            'sqlite',
            '',
            0,
            $targetPath,
            '',
            ''
        );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'sqlite',
            'sqlite'
        );

        $driver = new SqliteBackupDriver($connection);

        $driver->restoreDump($dump);

        $this->assertFileExists($targetPath);
        $this->assertSame(
            'original sqlite database',
            file_get_contents($targetPath)
        );

        unlink($targetPath);
    }

    private function driver(
        ?ProcessRunner $process = null
    ): SqliteBackupDriver {
        return new SqliteBackupDriver(
            $this->connection(),
            $process ?? $this->createMock(ProcessRunner::class)
        );
    }

    private function connection(): DatabaseConnection
    {
        return new DatabaseConnection(
            'sqlite',
            '',
            0,
            '/tmp/database.sqlite',
            '',
            ''
        );
    }
}
