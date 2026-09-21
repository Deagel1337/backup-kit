<?php

namespace Tests\Integration\MariaDb;

use Backup\Php\Application\BackupMariaDbApplication;
use Backup\Php\Application\Restore\RestoreMariaDbApplication;
use Backup\Php\DatabaseBackup\Driver\MariaDbBackupDriver;
use Backup\Php\DatabaseBackup\Model\DatabaseConnection;
use Backup\Php\Step\Backup\BackupDatabaseStep;
use Backup\Php\Step\Restore\CreateDatabaseBackupStep;
use Backup\Php\Step\Restore\RestoreDatabaseStep;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Integration\Support\MariaDbTestDatabase;

final class MariaDbBackupRestoreTest extends TestCase
{
    private MariaDbTestDatabase $database;

    private DatabaseConnection $sourceConnection;

    private DatabaseConnection $targetConnection;

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

        $this->targetConnection = new DatabaseConnection(
            driver: 'mariadb',
            host: '127.0.0.1',
            port: 3307,
            database: 'backup_target',
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

        $this->database->resetDatabase(
            $this->targetConnection->database
        );

        $this->database->importFixture(
            $this->sourceConnection->database,
            __DIR__ . '/../../Fixtures/database/source.sql'
        );
    }

    protected function tearDown(): void
    {
        $this->database->dropDatabase(
            $this->sourceConnection->database
        );

        $this->database->dropDatabase(
            $this->targetConnection->database
        );
    }

    public function testBackupAndRestore(): void
    {
        /*
         * ---------------------------------------------------------
         * Arrange
         * ---------------------------------------------------------
         */

        $backupDriver = new MariaDbBackupDriver(
            connection: $this->sourceConnection,
        );

        $restoreDriver = new MariaDbBackupDriver(
            connection: $this->targetConnection,
        );

        $backupApplication = BackupMariaDbApplication::create([
            new BackupDatabaseStep(
                driver: $backupDriver,
            ),
        ]);

        $restoreApplication = RestoreMariaDbApplication::create(
            driver: $restoreDriver,
            steps: [
                new RestoreDatabaseStep(
                    driver: $restoreDriver,
                ),
            ],
        );

        /*
         * ---------------------------------------------------------
         * Backup
         * ---------------------------------------------------------
         */

        $dump = $backupApplication->run(
            'mariadb-integration-test.sql'
        );

        self::assertFileExists($dump->path);

        /*
         * ---------------------------------------------------------
         * Restore
         * ---------------------------------------------------------
         */

        $restoreApplication->run($dump);

        /*
         * ---------------------------------------------------------
         * Assert
         * ---------------------------------------------------------
         */

        self::assertSame(
            $this->database->fetchTableData(
                $this->sourceConnection->database,
                'users'
            ),
            $this->database->fetchTableData(
                $this->targetConnection->database,
                'users'
            )
        );

        self::assertSame(
            $this->database->fetchTableData(
                $this->sourceConnection->database,
                'orders'
            ),
            $this->database->fetchTableData(
                $this->targetConnection->database,
                'orders'
            )
        );

        /*
         * ---------------------------------------------------------
         * Cleanup
         * ---------------------------------------------------------
         */

        if (is_file($dump->path)) {
            unlink($dump->path);
        }
    }

    public function testRestoreRollsBackWhenRestoreFails(): void
    {
        /*
         * ---------------------------------------------------------
         * Arrange
         * ---------------------------------------------------------
         */

        $backupDriver = new MariaDbBackupDriver(
            connection: $this->sourceConnection,
        );

        $restoreDriver = new MariaDbBackupDriver(
            connection: $this->targetConnection,
        );

        /*
         * Wir legen zunächst einen bekannten Zustand
         * in der Target-Datenbank an.
         */
        $this->database->importFixture(
            $this->targetConnection->database,
            __DIR__ . '/../../Fixtures/database/source.sql'
        );

        $originalUsers = $this->database->fetchTableData(
            $this->targetConnection->database,
            'users'
        );

        $originalOrders = $this->database->fetchTableData(
            $this->targetConnection->database,
            'orders'
        );

        /*
         * Backup-Application für den eigentlichen Source-Dump.
         */
        $backupApplication = BackupMariaDbApplication::create([
            new BackupDatabaseStep(
                driver: $backupDriver,
            ),
        ]);

        /*
         * Restore-Application bekommt jetzt den Safety-Dump-Step
         * und den eigentlichen Restore-Step.
         */
        $restoreApplication = RestoreMariaDbApplication::create(
            driver: $restoreDriver,
            steps: [
                new CreateDatabaseBackupStep(
                    driver: $restoreDriver,
                    backupName: 'before-restore.sql',
                ),
                new RestoreDatabaseStep(
                    driver: $restoreDriver,
                ),
            ],
        );

        /*
         * ---------------------------------------------------------
         * Create valid source dump through Application
         * ---------------------------------------------------------
         */

        $dump = $backupApplication->run(
            'mariadb-rollback-test.sql'
        );

        self::assertFileExists($dump->path);

        /*
         * ---------------------------------------------------------
         * Make the restore dump fail
         * ---------------------------------------------------------
         *
         * Wir erzeugen einen Dump, der zunächst die users-Tabelle
         * verändert/löscht und anschließend einen SQL-Fehler
         * verursacht.
         *
         * Dadurch muss der Restore fehlschlagen UND der Rollback
         * muss den ursprünglichen Zustand wiederherstellen.
         */

        $brokenDumpPath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'mariadb-broken-restore.sql';

        file_put_contents(
            $brokenDumpPath,
            <<<SQL
DROP TABLE users;

THIS IS INVALID SQL;

SQL
        );

        $brokenDump = new \Backup\Php\DatabaseBackup\Model\DatabaseDump(
            path: $brokenDumpPath,
            driver: 'mariadb',
            format: 'sql',
        );

        /*
         * ---------------------------------------------------------
         * Restore
         * ---------------------------------------------------------
         */

        $this->expectException(RuntimeException::class);

        try {
            $restoreApplication->run($brokenDump);
        } finally {
            /*
             * -----------------------------------------------------
             * Verify Rollback
             * -----------------------------------------------------
             */

            self::assertSame(
                $originalUsers,
                $this->database->fetchTableData(
                    $this->targetConnection->database,
                    'users'
                )
            );

            self::assertSame(
                $originalOrders,
                $this->database->fetchTableData(
                    $this->targetConnection->database,
                    'orders'
                )
            );

            /*
             * -----------------------------------------------------
             * Cleanup
             * -----------------------------------------------------
             */

            if (is_file($dump->path)) {
                unlink($dump->path);
            }

            if (is_file($brokenDumpPath)) {
                unlink($brokenDumpPath);
            }

            $rollbackPath = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'before-restore.sql';

            if (is_file($rollbackPath)) {
                unlink($rollbackPath);
            }
        }
    }
}