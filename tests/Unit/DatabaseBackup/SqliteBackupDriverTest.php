<?php

namespace Tests\DatabaseBackup\Driver;

use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use DatabaseBackup\Driver\SqliteBackupDriver\SqliteBackupDriver;

final class SqliteBackupDriverTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'sqlite_test_');
        file_put_contents($this->dumpPath, 'sqlite dump');
    }

    protected function tearDown(): void
    {
        unlink($this->dumpPath);
    }

    public function testAcceptsReadableNonEmptySqliteDump(): void
    {
        $driver = new SqliteBackupDriver($this->connection());

        $driver->validateDump(new DatabaseDump($this->dumpPath, 'sqlite', 'SQLITE'));
        $this->addToAssertionCount(1);
    }

    public function testRejectsWrongDriver(): void
    {
        $this->expectExceptionMessage('Der Dump gehört nicht zum SQLite-Treiber.');

        (new SqliteBackupDriver($this->connection()))->validateDump(new DatabaseDump($this->dumpPath, 'postgres', 'sqlite'));
    }

    public function testRejectsEmptyDump(): void
    {
        file_put_contents($this->dumpPath, '');
        $this->expectException(RuntimeException::class);
        $message = "Die Größe des Dumps konnte nicht ermittelt werden: {$this->dumpPath}";
        $this->expectExceptionMessage($message);

        (new SqliteBackupDriver($this->connection()))->validateDump(new DatabaseDump($this->dumpPath, 'sqlite', 'sqlite'));
    }

    private function connection(): DatabaseConnection
    {
        return new DatabaseConnection('sqlite', '', 0, '/tmp/database.sqlite', '', '');
    }
}