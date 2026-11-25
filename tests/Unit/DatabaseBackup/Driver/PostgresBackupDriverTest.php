<?php

namespace Tests\Unit\DatabaseBackup\Driver;

use Deagel1337\Backup\Kit\DatabaseBackup\Driver\PostgresBackupDriver;
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

final class PostgresBackupDriverTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'postgres_test_');

        if ($path === false) {
            throw new RuntimeException(
                'Test-Dump-Datei konnte nicht erstellt werden.'
            );
        }

        $this->dumpPath = $path;

        file_put_contents(
            $this->dumpPath,
            'CREATE TABLE test (id INT);'
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->dumpPath)) {
            unlink($this->dumpPath);
        }

        parent::tearDown();
    }

    /*
     * ---------------------------------------------------------
     * validateDump()
     * ---------------------------------------------------------
     */

    public function test_accepts_readable_non_empty_sql_dump(): void
    {
        $dump = new DatabaseDump(
            $this->dumpPath,
            'postgres',
            'SQL'
        );

        $this->driver()->validateDump($dump);

        $this->assertSame(
            'postgres',
            $dump->driver
        );
    }

    public function test_rejects_wrong_driver(): void
    {
        $this->expectException(InvalidDumpDriverException::class);
        $this->expectExceptionMessage(
            'Der Dump gehört nicht zum Postgres-Treiber.'
        );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'sqlite',
            'sql'
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
            'postgres',
            'sqlite'
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
            '/tmp/missing-postgres-dump',
            'postgres',
            'sql'
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
            'postgres',
            'sql'
        );

        $this->driver()->validateDump($dump);
    }

    /*
     * ---------------------------------------------------------
     * createDump()
     * ---------------------------------------------------------
     */

    public function test_create_dump_uses_pg_dump_command(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'pg_dump',
                    '--host=localhost',
                    '--port=5432',
                    '--username=user',
                    '--format=plain',
                    'db',
                ],
                ['PGPASSWORD' => 'password'],
                null,
                $this->isString(),
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
            'postgres',
            $dump->driver
        );

        $this->assertSame(
            'sql',
            $dump->format
        );

        $this->assertFileExists($dump->path);

        unlink($dump->path);
    }

    public function test_create_dump_throws_when_pg_dump_fails(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->willReturn(
                new ProcessResult(
                    1,
                    '',
                    'Access denied'
                )
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Das Postgres-Dump konnte nicht erstellt werden: Access denied'
        );

        $this->driver($process)->createDump();
    }

    /*
     * ---------------------------------------------------------
     * restoreDump()
     * ---------------------------------------------------------
     */

    public function test_restore_dump_uses_pg_command(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'psql',
                    '--host=localhost',
                    '--port=5432',
                    '--username=user',
                    'db',
                ],
                ['PGPASSWORD' => 'password'],
                null,
                null,
                $this->dumpPath
            )
            ->willReturn(
                new ProcessResult(
                    0,
                    '',
                    ''
                )
            );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'postgres',
            'sql'
        );

        $this->driver($process)->restoreDump($dump);

        $this->assertFileExists($dump->path);
    }

    public function test_restore_dump_throws_when_psql_fails(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->willReturn(
                new ProcessResult(
                    1,
                    '',
                    'Access denied'
                )
            );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'postgres',
            'sql'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Das Postgres-Dump konnte nicht wiederhergestellt werden: Access denied'
        );

        $this->driver($process)->restoreDump($dump);
    }

    /*
     * ---------------------------------------------------------
     * Helper
     * ---------------------------------------------------------
     */

    private function driver(
        ?ProcessRunner $process = null
    ): PostgresBackupDriver {
        return new PostgresBackupDriver(
            new DatabaseConnection(
                driver: 'postgres',
                host: 'localhost',
                port: 5432,
                database: 'db',
                username: 'user',
                password: 'password'
            ),
            $process ?? $this->createMock(ProcessRunner::class)
        );
    }
}
