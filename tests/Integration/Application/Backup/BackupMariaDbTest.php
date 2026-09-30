<?php

namespace Tests\Integration\Application\Backup;

use Deagel1337\Backup\Kit\Application\Backup\BackupMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Driver\MariaDbBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\Step\Backup\BackupDatabaseStep;
use PHPUnit\Framework\TestCase;
use Tests\Integration\Support\MariaDbTestDatabase;

final class BackupMariaDbTest extends TestCase
{
    private MariaDbTestDatabase $database;

    private DatabaseConnection $sourceConnection;

    protected function setUp(): void
    {
        $this->sourceConnection = new DatabaseConnection(
            driver: 'mariadb',
            host: '127.0.0.1',
            port: 3307,
            database: 'backup_source',
            username: 'root',
            password: 'root',
        );

        $this->database = new MariaDbTestDatabase(
            host: '127.0.0.1',
            port: 3307,
            username: 'root',
            password: 'root',
        );

        $this->database->resetDatabase(
            $this->sourceConnection->database
        );

        $this->database->importFixture(
            $this->sourceConnection->database,
            __DIR__ . '/../../../Fixtures/database/source.sql'
        );
    }

    protected function tearDown(): void
    {
        $this->database->dropDatabase(
            $this->sourceConnection->database
        );

        parent::tearDown();
    }

    public function testCreatesMariaDbBackup(): void
    {
        $driver = new MariaDbBackupDriver(
            connection: $this->sourceConnection,
        );

        $application = BackupMariaDbApplication::create([
            new BackupDatabaseStep(
                driver: $driver,
            ),
        ]);

        $backupPath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'mariadb-application-test.sql';

        $dump = $application->run($backupPath);

        self::assertSame(
            $backupPath,
            $dump->path
        );

        self::assertSame(
            'mariadb',
            $dump->driver
        );

        self::assertSame(
            'sql',
            $dump->format
        );

        self::assertFileExists(
            $dump->path
        );

        self::assertGreaterThan(
            0,
            filesize($dump->path)
        );

        $content = file_get_contents($dump->path);

        self::assertNotFalse($content);

        self::assertStringContainsString(
            'CREATE TABLE',
            $content
        );

        self::assertStringContainsString(
            'users',
            $content
        );

        self::assertStringContainsString(
            'orders',
            $content
        );

        if (is_file($dump->path)) {
            unlink($dump->path);
        }
    }
}
