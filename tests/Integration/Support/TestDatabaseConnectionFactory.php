<?php

namespace Tests\Integration\Support;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;

final class TestDatabaseConnectionFactory
{
    public static function source(): DatabaseConnection
    {
        return self::connection('backup_source');
    }

    public static function target(): DatabaseConnection
    {
        return self::connection('backup_target');
    }

    public static function host(): string
    {
        return getenv('BACKUP_KIT_TEST_DB_HOST') ?: '127.0.0.1';
    }

    public static function port(): int
    {
        $port = getenv('BACKUP_KIT_TEST_DB_PORT');

        return $port === false ? 3307 : (int) $port;
    }

    public static function username(): string
    {
        return getenv('BACKUP_KIT_TEST_DB_USERNAME') ?: 'root';
    }

    public static function password(): string
    {
        return getenv('BACKUP_KIT_TEST_DB_PASSWORD') ?: 'root';
    }

    public static function server(): MariaDbTestDatabase
    {
        return new MariaDbTestDatabase(
            host: self::host(),
            port: self::port(),
            username: self::username(),
            password: self::password(),
        );
    }

    private static function connection(string $database): DatabaseConnection
    {
        return new DatabaseConnection(
            driver: 'mariadb',
            host: self::host(),
            port: self::port(),
            database: $database,
            username: self::username(),
            password: self::password(),
        );
    }
}
