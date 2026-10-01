<?php

namespace Tests\Unit\DatabaseBackup\Driver;

use Deagel1337\Backup\Kit\DatabaseBackup\Driver\MariaDbBackupDriver;
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

        parent::tearDown();
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
            $backupName,
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

        $outputFile = '';

        $directory = sys_get_temp_dir() . '/backup-test-' . uniqid();
        mkdir($directory, 0777, true);

        $destination = $directory . '/backup.sql';

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

                    if($outputFile === null) {
                        return new ProcessResult(1, '', 'Filename is missing');
                    }
                    // Simuliere, dass mariadb-dump eine Datei erzeugt hat,
                    // bevor der Prozess fehlschlägt.
                    file_put_contents($outputFile, 'partial dump');

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
            $this->driver($process)->createDump($destination);
        } finally {
            $this->assertNotNull($outputFile);
            $this->assertFileDoesNotExist($outputFile);

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testCreateDumpUsesMariaDbDumpCommand(): void
    {
        $process = $this->createMock(ProcessRunner::class);

        $directory = sys_get_temp_dir() . '/backup-test-' . uniqid();
        mkdir($directory, 0777, true);

        $destination = $directory . '/backup.sql';

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
                $destination,
                null
            )
            ->willReturn(
                new ProcessResult(
                    0,
                    '',
                    ''
                )
            );

        try {
            $dump = $this->driver($process)->createDump($destination);

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

            $this->assertSame(
                $destination,
                $dump->path
            );
        } finally {
            if (file_exists($destination)) {
                unlink($destination);
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
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

        $directory = sys_get_temp_dir() . '/backup-test-' . uniqid();

        mkdir($directory, 0777, true);

        $destination = $directory . '/backup.sql';

        try {
            // Test
            $this->driver($process)->createDump($destination);
        } finally {
            rmdir($directory);
        }

        $this->driver($process)->createDump($destination);
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