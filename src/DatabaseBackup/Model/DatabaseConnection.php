<?php

namespace DatabaseBackup\Model\DatabaseConnection;

final class DatabaseConnection
{
    public function __construct(
        public string $driver,
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        public string $password,
    ) {}
}