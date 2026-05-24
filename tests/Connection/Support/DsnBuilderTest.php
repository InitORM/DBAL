<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Connection\Support;

use InitORM\DBAL\Connection\Support\DsnBuilder;
use PHPUnit\Framework\TestCase;

final class DsnBuilderTest extends TestCase
{
    /**
     * Regression for BUG-1: in 1.x the auto-built DSN put the charset value
     * into the `dbname=` slot, so PDO connected to a non-existent database
     * named "utf8mb4".
     */
    public function test_builds_mysql_dsn_with_correct_dbname(): void
    {
        $dsn = (new DsnBuilder())->build([
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => 'app',
            'charset'  => 'utf8mb4',
        ]);

        self::assertSame('mysql:host=127.0.0.1;port=3306;dbname=app;charset=utf8mb4', $dsn);
    }

    public function test_omits_dbname_and_charset_when_empty(): void
    {
        $dsn = (new DsnBuilder())->build([
            'driver' => 'mysql',
            'host'   => 'db',
            'port'   => 3306,
        ]);

        self::assertSame('mysql:host=db;port=3306', $dsn);
    }

    public function test_builds_pgsql_dsn_without_charset(): void
    {
        $dsn = (new DsnBuilder())->build([
            'driver'   => 'pgsql',
            'host'     => 'pg',
            'port'     => 5432,
            'database' => 'app',
            'charset'  => 'utf8',
        ]);

        self::assertSame('pgsql:host=pg;port=5432;dbname=app', $dsn);
    }

    public function test_builds_sqlite_memory_dsn(): void
    {
        self::assertSame('sqlite::memory:', (new DsnBuilder())->build([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]));
    }

    public function test_builds_sqlite_file_dsn(): void
    {
        self::assertSame('sqlite:/tmp/db.sqlite', (new DsnBuilder())->build([
            'driver'   => 'sqlite',
            'database' => '/tmp/db.sqlite',
        ]));
    }

    public function test_falls_back_to_mysql_defaults_when_driver_unknown(): void
    {
        $dsn = (new DsnBuilder())->build([
            'driver'   => 'mariadb',
            'database' => 'app',
        ]);

        self::assertStringStartsWith('mariadb:host=127.0.0.1;port=3306;dbname=app', $dsn);
    }
}
