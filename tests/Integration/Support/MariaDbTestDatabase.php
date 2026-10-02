<?php

namespace Tests\Integration\Support;

use PDO;
use RuntimeException;

final class MariaDbTestDatabase
{
    private PDO $serverConnection;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
    ) {
        if (! extension_loaded('pdo_mysql')) {
            throw new RuntimeException(
                'Die PHP Extension pdo_mysql ist nicht installiert.'
            );
        }

        $this->serverConnection = new PDO(
            sprintf(
                'mysql:host=%s;port=%d',
                $this->host,
                $this->port
            ),
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function createDatabase(string $database): void
    {
        $this->serverConnection->exec(
            sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s`',
                $this->escapeIdentifier($database)
            )
        );
    }

    public function dropDatabase(string $database): void
    {
        $this->serverConnection->exec(
            sprintf(
                'DROP DATABASE IF EXISTS `%s`',
                $this->escapeIdentifier($database)
            )
        );
    }

    public function resetDatabase(string $database): void
    {
        $this->dropDatabase($database);
        $this->createDatabase($database);
    }

    public function execute(string $database, string $sql): void
    {
        $pdo = $this->connect($database);

        $pdo->exec($sql);
    }

    public function importFixture(
        string $database,
        string $fixture
    ): void {
        if (! is_file($fixture)) {
            throw new RuntimeException(
                "Die Fixture-Datei existiert nicht: {$fixture}"
            );
        }

        $sql = file_get_contents($fixture);

        if ($sql === false) {
            throw new RuntimeException(
                "Die Fixture-Datei konnte nicht gelesen werden: {$fixture}"
            );
        }

        $this->execute($database, $sql);
    }

    public function tableExists(
        string $database,
        string $table
    ): bool {
        $pdo = $this->connect($database);

        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = :database
             AND table_name = :table'
        );

        $statement->execute([
            'database' => $database,
            'table' => $table,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $database, string $sql): array
    {
        $pdo = $this->connect($database);

        $statement = $pdo->query($sql);

        if ($statement === false) {
            throw new RuntimeException(
                'Die Abfrage konnte nicht ausgeführt werden.'
            );
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $statement->fetchAll();

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchTableData(
        string $database,
        string $table
    ): array {
        $pdo = $this->connect($database);

        $statement = $pdo->query(
            sprintf(
                'SELECT * FROM `%s` ORDER BY 1',
                $this->escapeIdentifier($table)
            )
        );

        if ($statement === false) {
            throw new RuntimeException(
                "Die Tabelle konnte nicht gelesen werden: {$table}"
            );
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $statement->fetchAll();

        return $result;
    }

    public function count(
        string $database,
        string $table
    ): int {
        $pdo = $this->connect($database);

        $statement = $pdo->query(
            sprintf(
                'SELECT COUNT(*) FROM `%s`',
                $this->escapeIdentifier($table)
            )
        );

        if ($statement === false) {
            throw new RuntimeException(
                "Die Tabelle konnte nicht gelesen werden: {$table}"
            );
        }

        return (int) $statement->fetchColumn();
    }

    public function connect(string $database): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s',
                $this->host,
                $this->port,
                $database
            ),
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }
}
