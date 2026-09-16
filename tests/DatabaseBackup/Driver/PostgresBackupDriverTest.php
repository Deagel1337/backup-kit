<?php

namespace Tests\DatabaseBackup\Driver;

use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Src\DatabaseBackup\Driver\PostgresBackupDriver\PostgresBackupDriver;

final class PostgresBackupDriverTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'postgres_test_');
        file_put_contents($this->dumpPath, 'sql dump');
    }

    protected function tearDown(): void
    {
        unlink($this->dumpPath);
    }

    public function testAcceptsReadableNonEmptySqlDump(): void
    {
        $this->driver()->validateDump(new DatabaseDump($this->dumpPath, 'postgres', 'SQL'));
        $this->addToAssertionCount(1);
    }

    public function testRejectsWrongDriver(): void
    {
        $this->expectExceptionMessage('Der Dump gehört nicht zum Postgres-Treiber.');

        $this->driver()->validateDump(new DatabaseDump($this->dumpPath, 'sqlite', 'sql'));
    }

    public function testRejectsMissingDump(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Dump-Datei existiert nicht.');

        $this->driver()->validateDump(new DatabaseDump('/tmp/missing-postgres-dump', 'postgres', 'sql'));
    }

    private function driver(): PostgresBackupDriver
    {
        return new PostgresBackupDriver(new DatabaseConnection('postgres', 'localhost', 5432, 'db', 'user', 'password'));
    }
}