<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Support;

use InitORM\DBAL\Connection\Connection;

/**
 * Test helper — builds a fresh SQLite in-memory Connection, optionally seeded
 * with a `users` table.
 */
final class SqliteHelper
{
    /**
     * @param array<string, mixed> $overrides
     */
    public static function makeConnection(array $overrides = []): Connection
    {
        $credentials = array_merge([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'charset'  => '',
        ], $overrides);

        return new Connection($credentials);
    }

    public static function seedUsers(Connection $connection): void
    {
        $pdo = $connection->getPDO();
        $pdo->exec('CREATE TABLE users (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            name     TEXT NOT NULL,
            email    TEXT NOT NULL,
            active   INTEGER NOT NULL DEFAULT 1,
            score    INTEGER,
            metadata TEXT
        )');
        $pdo->exec("INSERT INTO users (name, email, active, score, metadata)
                    VALUES ('Alice', 'alice@example.com', 1, 42, NULL),
                           ('Bob',   'bob@example.com',   0, 13, '{\"role\":\"admin\"}')");
    }
}
