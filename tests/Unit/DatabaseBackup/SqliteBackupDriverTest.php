<?php

namespace Tests\DatabaseBackup\Driver;

use DatabaseBackup\Driver\SqliteBackupDriver\SqliteBackupDriver;
use DatabaseBackup\Exception\DumpNotFoundException;
use DatabaseBackup\Exception\DumpNotReadableException;
use DatabaseBackup\Exception\EmptyDumpException;
use DatabaseBackup\Exception\InvalidDumpDriverException;
use DatabaseBackup\Exception\InvalidDumpFormatException;
use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use Process\Model\ProcessResult;
use Process\ProcessRunner\ProcessRunner;
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
    }

    public function testAcceptsReadableNonEmptySqliteDump(): void
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

    public function testRejectsWrongDriver(): void
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

    public function testRejectsWrongFormat(): void
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

    public function testRejectsMissingDump(): void
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

    public function testRejectsEmptyDump(): void
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

    public function testCreateDumpUsesSqliteBackupCommand(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->callback(
                    function (array $command): bool {
                        return $command[0] === 'sqlite3'
                            && $command[1] === '/tmp/database.sqlite'
                            && str_starts_with($command[2], '.backup ')
                            && str_contains($command[2], '.backup');
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

    public function testCreateDumpThrowsWhenSqliteFails(): void
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

    public function testCreateDumpUsesProvidedBackupName(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $backupName = 'test-backup.sqlite';

        $expectedPath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . $backupName;

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'sqlite3',
                    '/tmp/database.sqlite',
                    ".backup '" . $expectedPath . "'",
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

    public function testRestoreDumpCopiesDatabaseFile(): void
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