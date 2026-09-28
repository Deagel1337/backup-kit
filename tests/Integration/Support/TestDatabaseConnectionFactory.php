<?php

namespace Tests\Integration\Support;

use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;

final class TestDatabaseConnectionFactory
{
    public static function source(): DatabaseConnection
    {
        return new DatabaseConnection(
            driver: 'mysql',
            host: '127.0.0.1',
            port: 3307,
            database: 'backup_source',
            username: 'root',
            password: 'root'
        );
    }

    public static function target(): DatabaseConnection
    {
        return new DatabaseConnection(
            driver: 'mysql',
            host: '127.0.0.1',
            port: 3307,
            database: 'backup_source',
            username: 'root',
            password: 'root'
        );
    }
}