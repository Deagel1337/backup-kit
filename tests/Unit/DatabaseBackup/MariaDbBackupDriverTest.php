<?php

namespace Tests\Unit\DatabaseBackup;

use Backup\Php\DatabaseBackup\Driver\MariaDbBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\DatabaseBackup\Model\DatabaseDump;
use Backup\Php\Exception\DumpDriverException\DumpNotFoundException;
use Backup\Php\Exception\DumpDriverException\EmptyDumpException;
use Backup\Php\Exception\DumpDriverException\InvalidDumpDriverException;
use Backup\Php\Exception\DumpDriverException\InvalidDumpFormatException;
use Backup\Php\Process\Interface\ProcessRunner;
use Backup\Php\Process\Model\ProcessResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;



final class MariaDbBackupDriverTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mariadb_test_');

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
    }

    /*
     * ---------------------------------------------------------
     * validateDump()
     * ---------------------------------------------------------
     */

    public function testAcceptsReadableNonEmptySqlDump(): void
    {
        $dump = new DatabaseDump(
            $this->dumpPath,
            'mariadb',
            'SQL'
        );

        $this->driver()->validateDump($dump);

        $this->assertSame(
            'mariadb',
            $dump->driver
        );
    }

    public function testRejectsWrongDriver(): void
    {
        $this->expectException(InvalidDumpDriverException::class);
        $this->expectExceptionMessage(
            'Der Dump gehört nicht zum MariaDB-Treiber.'
        );

        $dump = new DatabaseDump(
            $this->dumpPath,
            'mysql',
            'sql'
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
            'mariadb',
            'sqlite'
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
            '/tmp/missing-mariadb-dump',
            'mariadb',
            'sql'
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
            'mariadb',
            'sql'
        );

        $this->driver()->validateDump($dump);
    }

    /*
     * ---------------------------------------------------------
     * createDump()
     * ---------------------------------------------------------
     */

    public function testCreateDumpUsesProvidedBackupName(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'mariadb-dump',
                    '--host=localhost',
                    '--port=3306',
                    '--user=user',
                    'db',
                ],
                ['MYSQL_PWD' => 'password'],
                null,
                $this->isType('string'),
                null
            )
            ->willReturn(
                new ProcessResult(
                    0,
                    '',
                    ''
                )
            );

        $backupName = 'test-backup.sql';

        $dump = $this->driver($process)->createDump($backupName);

        $this->assertSame(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . $backupName,
            $dump->path
        );

        $this->assertSame(
            'mariadb',
            $dump->driver
        );

        $this->assertSame(
            'sql',
            $dump->format
        );

        if (is_file($dump->path)) {
            unlink($dump->path);
        }
    }

    public function testCreateDumpRemovesDumpFileWhenMariaDbDumpFails(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $outputFile = null;

        $process
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(
                function (
                    array $command,
                    array $environment,
                    ?string $workingDirectory,
                    ?string $outputFileArgument,
                    ?string $inputFile
                ) use (&$outputFile): ProcessResult {
                    $outputFile = $outputFileArgument;

                    return new ProcessResult(
                        1,
                        '',
                        'Access denied'
                    );
                }
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Das MariaDB-Dump konnte nicht erstellt werden: Access denied'
        );

        try {
            $this->driver($process)->createDump();
        } finally {
            $this->assertNotNull($outputFile);
            if($outputFile !== null) {
                $this->assertFileDoesNotExist($outputFile);
            }
        }
    }

    public function testCreateDumpUsesMariaDbDumpCommand(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'mariadb-dump',
                    '--host=localhost',
                    '--port=3306',
                    '--user=user',
                    'db',
                ],
                ['MYSQL_PWD' => 'password'],
                null,
                $this->isType('string'),
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
            'mariadb',
            $dump->driver
        );

        $this->assertSame(
            'sql',
            $dump->format
        );

        $this->assertFileExists($dump->path);

        unlink($dump->path);
    }

    public function testCreateDumpThrowsWhenMariaDbDumpFails(): void
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
            'Das MariaDB-Dump konnte nicht erstellt werden: Access denied'
        );

        $this->driver($process)->createDump();
    }

    /*
     * ---------------------------------------------------------
     * restoreDump()
     * ---------------------------------------------------------
     */

    public function testRestoreDumpUsesMariaDbCommand(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $process
            ->expects($this->once())
            ->method('run')
            ->with(
                [
                    'mariadb',
                    '--host=localhost',
                    '--port=3306',
                    '--user=user',
                    'db',
                ],
                ['MYSQL_PWD' => 'password'],
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
            'mariadb',
            'sql'
        );

        $this->driver($process)->restoreDump($dump);

        $this->assertFileExists($dump->path);
    }

    public function testRestoreDumpThrowsWhenMariaDbFails(): void
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
            'mariadb',
            'sql'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Das MariaDB-Dump konnte nicht wiederhergestellt werden: Access denied'
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
    ): MariaDbBackupDriver {
        return new MariaDbBackupDriver(
            new DatabaseConnection(
                driver: 'mariadb',
                host: 'localhost',
                port: 3306,
                database: 'db',
                username: 'user',
                password: 'password'
            ),
            $process ?? $this->createMock(ProcessRunner::class)
        );
    }
}