<?php

namespace Tests\DatabaseBackup\Driver;

use DatabaseBackup\Model\DatabaseConnection\DatabaseConnection;
use DatabaseBackup\Model\DatabaseDump\DatabaseDump;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use DatabaseBackup\Driver\MariaDbDriver\MariaDbBackupDriver;

final class MariaDbBackupDriverTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        $this->dumpPath = tempnam(sys_get_temp_dir(), 'mariadb_test_');
        file_put_contents($this->dumpPath, 'sql dump');
    }

    protected function tearDown(): void
    {
        unlink($this->dumpPath);
    }

    public function testAcceptsReadableNonEmptySqlDump(): void
    {
        $this->driver()->validateDump(new DatabaseDump($this->dumpPath, 'mariadb', 'SQL'));
        $this->addToAssertionCount(1);
    }

    public function testRejectsWrongFormat(): void
    {
        $this->expectExceptionMessage('Der Dump muss im SQL-Format vorliegen.');

        $this->driver()->validateDump(new DatabaseDump($this->dumpPath, 'mariadb', 'sqlite'));
    }

    public function testRejectsMissingDump(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Die Dump-Datei existiert nicht.');

        $this->driver()->validateDump(new DatabaseDump('/tmp/missing-mariadb-dump', 'mariadb', 'sql'));
    }

    private function driver(): MariaDbBackupDriver
    {
        return new MariaDbBackupDriver(new DatabaseConnection('mariadb', 'localhost', 3306, 'db', 'user', 'password'));
    }
}