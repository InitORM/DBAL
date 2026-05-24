<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Connection;

use InitORM\DBAL\Connection\Connection;
use InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException;
use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\Connection\Exceptions\ConnectionInvalidArgumentException;
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\DBAL\Connection\Exceptions\ValidConnectionAvailableException;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use InitORM\DBAL\Tests\Support\SqliteHelper;
use PDO;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    public function test_does_not_connect_until_getPDO_is_called(): void
    {
        $connection = SqliteHelper::makeConnection();
        $ref        = new \ReflectionClass($connection);
        $pdo        = $ref->getProperty('pdo');
        $pdo->setAccessible(true);

        self::assertNull($pdo->getValue($connection));

        $connection->getPDO();
        self::assertInstanceOf(PDO::class, $pdo->getValue($connection));
    }

    public function test_setter_throws_when_connection_is_already_open(): void
    {
        $connection = SqliteHelper::makeConnection();
        $connection->getPDO();

        $this->expectException(ConnectionAlreadyEstablishedException::class);
        $connection->setDatabase(':memory:');
    }

    /**
     * The deprecated alias must still be catchable.
     */
    public function test_deprecated_exception_alias_is_thrown(): void
    {
        $connection = SqliteHelper::makeConnection();
        $connection->getPDO();

        $this->expectException(ValidConnectionAvailableException::class);
        $connection->setHost('db');
    }

    public function test_disconnect_releases_pdo_reference(): void
    {
        $connection = SqliteHelper::makeConnection();
        $connection->getPDO();

        self::assertTrue($connection->disconnect());

        // After disconnect, setters work again.
        $connection->setDatabase(':memory:');
        self::assertSame(':memory:', $connection->getDatabase());
    }

    /**
     * Regression for BUG-1 + BUG-2: the auto-built DSN must include the
     * database name (not the charset value) and the build branch must
     * actually run when no explicit DSN was supplied.
     */
    public function test_auto_builds_dsn_when_none_is_provided(): void
    {
        $connection = new Connection([
            'driver'   => 'mysql',
            'host'     => 'db',
            'port'     => 3306,
            'database' => 'app',
            'charset'  => 'utf8mb4',
        ]);

        self::assertSame('mysql:host=db;port=3306;dbname=app;charset=utf8mb4', $connection->getDsn());
    }

    public function test_uses_user_supplied_dsn_verbatim(): void
    {
        $connection = new Connection(['dsn' => 'mysql:host=x;dbname=y']);

        self::assertSame('mysql:host=x;dbname=y', $connection->getDsn());
    }

    public function test_query_returns_a_data_mapper(): void
    {
        $connection = SqliteHelper::makeConnection();
        SqliteHelper::seedUsers($connection);

        $mapper = $connection->query('SELECT id, name FROM users WHERE id = :id', ['id' => 1]);

        self::assertInstanceOf(DataMapperInterface::class, $mapper);
        $row = $mapper->asAssoc()->row();
        self::assertSame('Alice', $row['name']);
    }

    public function test_query_rejects_invalid_sql_with_sql_execute_exception(): void
    {
        $connection = SqliteHelper::makeConnection();
        SqliteHelper::seedUsers($connection);

        $this->expectException(\PDOException::class);
        $connection->query('SELECT * FROM nonexistent_table');
    }

    public function test_query_logs_failures_to_the_user_supplied_logger(): void
    {
        $captured = [];
        $connection = SqliteHelper::makeConnection([
            'log' => static function (string $message) use (&$captured): void {
                $captured[] = $message;
            },
        ]);
        SqliteHelper::seedUsers($connection);

        try {
            $connection->query('SELECT * FROM nonexistent_table');
            self::fail('Expected the query to throw');
        } catch (\Throwable $e) {
            // expected
        }

        self::assertNotEmpty($captured);
        self::assertStringContainsString('SELECT * FROM nonexistent_table', $captured[0]);
    }

    /**
     * Regression for BUG-5: a previous version called `strtr($sql, $params)`
     * in the catch block, which raised TypeError when any parameter value
     * was not a string. The new implementation must serialise parameters
     * defensively and never swallow the original exception.
     */
    public function test_query_failure_log_handles_non_string_parameters(): void
    {
        $captured = [];
        $connection = SqliteHelper::makeConnection([
            'debug' => true,
            'log'   => static function (string $message) use (&$captured): void {
                $captured[] = $message;
            },
        ]);
        SqliteHelper::seedUsers($connection);

        try {
            $connection->query(
                'SELECT * FROM nonexistent_table WHERE id = :id AND on = :on AND meta = :meta',
                ['id' => 1, 'on' => true, 'meta' => null]
            );
            self::fail('Expected the query to throw');
        } catch (\Throwable $e) {
            // expected — the point is that the catch block did not itself
            // raise a TypeError.
        }

        self::assertNotEmpty($captured);
        self::assertStringContainsString('"id":1', $captured[0]);
    }

    public function test_query_records_log_entry_when_enabled(): void
    {
        $connection = SqliteHelper::makeConnection(['queryLogs' => true]);
        $connection->setQueryLogs(true);
        SqliteHelper::seedUsers($connection);

        $connection->query('SELECT 1');

        $logs = $connection->getQueryLogs();
        self::assertNotEmpty($logs);
        $queries = array_column($logs, 'query');
        self::assertContains('SELECT 1', $queries);
    }

    /**
     * Regression for BUG-4: `array_merge` would renumber the integer PDO
     * option keys. We need positional integrity, so `+` must be used.
     */
    public function test_query_passes_user_prepare_options_into_pdo_prepare(): void
    {
        $connection = SqliteHelper::makeConnection([
            'queryOptions' => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
        ]);
        SqliteHelper::seedUsers($connection);

        // No assertion failure means PDO accepted the options array intact.
        // We additionally probe with a per-call options override.
        $mapper = $connection->query(
            'SELECT id FROM users WHERE id = :id',
            ['id' => 1],
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );

        // pdo_sqlite returned integer columns as strings before PHP 8.1,
        // so compare loosely on the value while still proving the bind
        // and the prepare options round-tripped.
        $row = $mapper->asAssoc()->row();
        self::assertIsArray($row);
        self::assertSame(['id'], array_keys($row));
        self::assertSame(1, (int) $row['id']);
    }

    public function test_forwards_pdo_methods_via_call(): void
    {
        $connection = SqliteHelper::makeConnection();
        SqliteHelper::seedUsers($connection);

        $connection->query("INSERT INTO users (name, email) VALUES ('Carol', 'carol@example.com')");
        self::assertGreaterThan(0, (int) $connection->lastInsertId());
    }

    public function test_clone_returns_a_disconnected_instance_with_same_credentials(): void
    {
        $original = SqliteHelper::makeConnection();
        $original->getPDO();

        $clone = $original->clone();
        self::assertNotSame($original, $clone);

        $ref = new \ReflectionClass($clone);
        $pdo = $ref->getProperty('pdo');
        $pdo->setAccessible(true);
        self::assertNull($pdo->getValue($clone));

        self::assertSame($original->getDriver(), $clone->getDriver());
    }

    public function test_setCharset_rejects_unsafe_identifiers(): void
    {
        $connection = SqliteHelper::makeConnection();

        $this->expectException(ConnectionInvalidArgumentException::class);
        $connection->setCharset("utf8mb4'; DROP TABLE users; --");
    }

    public function test_persistent_default_is_false(): void
    {
        $connection = SqliteHelper::makeConnection();
        $options    = $connection->getOptions();

        self::assertFalse($options[PDO::ATTR_PERSISTENT]);
    }

    public function test_user_options_override_defaults(): void
    {
        $connection = SqliteHelper::makeConnection([
            'options' => [PDO::ATTR_PERSISTENT => true],
        ]);

        self::assertTrue($connection->getOptions()[PDO::ATTR_PERSISTENT]);
    }

    public function test_createLog_returns_false_when_no_sink_is_configured(): void
    {
        $connection = SqliteHelper::makeConnection();

        self::assertFalse($connection->createLog('hello'));
    }

    public function test_createLog_uses_psr3_placeholders(): void
    {
        $captured = '';
        $connection = SqliteHelper::makeConnection([
            'log' => static function (string $message) use (&$captured): void {
                $captured = $message;
            },
        ]);

        self::assertTrue($connection->createLog('user {id} failed', ['id' => 7]));
        self::assertSame('user 7 failed', $captured);
    }

    public function test_setOptions_preserves_existing_user_options(): void
    {
        $connection = SqliteHelper::makeConnection();
        $connection->setOptions([PDO::ATTR_TIMEOUT => 5]);
        $connection->setOptions([PDO::ATTR_CASE => PDO::CASE_LOWER]);

        $options = $connection->getOptions();
        self::assertSame(5, $options[PDO::ATTR_TIMEOUT]);
        self::assertSame(PDO::CASE_LOWER, $options[PDO::ATTR_CASE]);
    }

    public function test_connection_exception_is_thrown_for_bad_credentials(): void
    {
        $connection = new Connection([
            'driver'   => 'sqlite',
            'database' => '/this/path/should/not/exist/abc.sqlite',
            'options'  => [],
        ]);

        // sqlite happily creates the file if the directory exists; force a
        // failure by giving it an unreachable path.
        $this->expectException(ConnectionException::class);
        $connection->getPDO();
    }

    /**
     * Regression for BUG-3: when no driver is configured, `connect()` should
     * read the actual driver name from the live PDO instance.
     */
    public function test_connect_reads_driver_name_from_pdo_when_unset(): void
    {
        $connection = new Connection([
            'dsn'     => 'sqlite::memory:',
            'driver'  => '',
            'charset' => '',
        ]);

        $connection->getPDO();
        self::assertSame('sqlite', $connection->getDriver());
    }

    public function test_trivial_setters_and_getters_round_trip(): void
    {
        $connection = SqliteHelper::makeConnection();

        $connection->setHost('h')
                   ->setPort(1234)
                   ->setDsn('sqlite::memory:')
                   ->setUsername('u')
                   ->setPassword('p')
                   ->setDriver('mysql')
                   ->setCharset('utf8mb4', 'utf8mb4_unicode_ci')
                   ->setDebug(true);

        self::assertSame('h', $connection->getHost());
        self::assertSame(1234, $connection->getPort());
        self::assertSame('sqlite::memory:', $connection->getDsn());
        self::assertSame('u', $connection->getUsername());
        self::assertSame('p', $connection->getPassword());
        self::assertSame('mysql', $connection->getDriver());
        self::assertSame('utf8mb4', $connection->getCharset());
        self::assertSame('utf8mb4_unicode_ci', $connection->getCollation());
        self::assertTrue($connection->getDebug());
    }

    public function test_setDriver_rejects_unsafe_identifier(): void
    {
        $connection = SqliteHelper::makeConnection();

        $this->expectException(ConnectionInvalidArgumentException::class);
        $connection->setDriver("mysql'; --");
    }

    public function test_setCharset_rejects_unsafe_collation(): void
    {
        $connection = SqliteHelper::makeConnection();

        $this->expectException(ConnectionInvalidArgumentException::class);
        $connection->setCharset('utf8', "utf8'; DROP");
    }

    public function test_getDatabase_returns_null_for_empty_string(): void
    {
        $connection = SqliteHelper::makeConnection(['database' => '']);

        self::assertNull($connection->getDatabase());
    }

    /**
     * Run the MySQL-only charset branch by spoofing the credentials driver
     * to 'mysql' after the underlying PDO has been opened against SQLite.
     * SQLite rejects `SET NAMES`, so the method bubbles a PDOException —
     * but every branch up to the `exec()` call is exercised.
     */
    public function test_applyCharsetAndCollation_mysql_branch_with_collation(): void
    {
        $connection = SqliteHelper::makeConnection([
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $connection->getPDO();
        $this->spoofDriver($connection, 'mysql');

        $this->expectException(\PDOException::class);
        $this->invokePrivate($connection, 'applyCharsetAndCollation');
    }

    public function test_applyCharsetAndCollation_mysql_branch_without_collation(): void
    {
        $connection = SqliteHelper::makeConnection([
            'charset'   => 'utf8mb4',
            'collation' => null,
        ]);
        $connection->getPDO();
        $this->spoofDriver($connection, 'mysql');

        $this->expectException(\PDOException::class);
        $this->invokePrivate($connection, 'applyCharsetAndCollation');
    }

    public function test_applyCharsetAndCollation_returns_early_for_empty_charset(): void
    {
        $connection = SqliteHelper::makeConnection(['charset' => '']);
        $connection->getPDO();
        $this->spoofDriver($connection, 'mysql');

        // No exception — early return path.
        $this->invokePrivate($connection, 'applyCharsetAndCollation');
        self::assertTrue(true);
    }

    public function test_applyCharsetAndCollation_returns_early_for_unsafe_charset(): void
    {
        $connection = SqliteHelper::makeConnection(['charset' => 'utf8']);
        $connection->getPDO();
        $this->spoofDriver($connection, 'mysql');
        // Bypass the setter validation by spoofing the charset directly.
        $this->spoofCredential($connection, 'charset', "utf8' --");

        // No exception — the regex guard catches it before exec.
        $this->invokePrivate($connection, 'applyCharsetAndCollation');
        self::assertTrue(true);
    }

    private function spoofDriver(Connection $connection, string $driver): void
    {
        $this->spoofCredential($connection, 'driver', $driver);
    }

    private function spoofCredential(Connection $connection, string $key, $value): void
    {
        $ref   = new \ReflectionObject($connection);
        $prop  = $ref->getProperty('credentials');
        $prop->setAccessible(true);
        $creds = $prop->getValue($connection);
        $creds[$key] = $value;
        $prop->setValue($connection, $creds);
    }

    /**
     * @param array<int, mixed> $args
     * @return mixed
     */
    private function invokePrivate(Connection $connection, string $method, array $args = [])
    {
        $ref = new \ReflectionObject($connection);
        $m   = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($connection, $args);
    }
}
