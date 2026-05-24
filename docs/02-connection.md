# 02 · Connection

`InitORM\DBAL\Connection\Connection` is the single entry point. This page
covers credentials, lifecycle, and cloning.

## Credentials

The constructor accepts an associative array merged on top of these
defaults:

| Key            | Default            | Notes                                                  |
|----------------|--------------------|--------------------------------------------------------|
| `dsn`          | `''`               | If empty, auto-built from `driver`/`host`/etc.         |
| `driver`       | `'mysql'`          | `mysql`, `pgsql`, `sqlite`, or anything PDO supports.  |
| `host`         | `'127.0.0.1'`      |                                                        |
| `port`         | `3306`             | Use `5432` for PostgreSQL.                             |
| `database`     | `''`               |                                                        |
| `username`     | `null`             |                                                        |
| `password`     | `null`             |                                                        |
| `charset`      | `'utf8mb4'`        | MySQL-only `SET NAMES`. Pass `''` to skip.             |
| `collation`    | `null`             | When set, appended as `COLLATE` to `SET NAMES` (MySQL).|
| `options`      | `[]`               | PDO attribute map; merged with library defaults.       |
| `queryOptions` | `[]`               | Default PDO prepare options applied to every query.    |
| `log`          | `null`             | See [06 · Logging](06-logging.md).                     |
| `debug`        | `false`            | When true, query failure logs include serialised args. |
| `queryLogs`    | `false`            | When true, every query is appended to the log buffer.  |

### MySQL

```php
$db = new Connection([
    'driver'   => 'mysql',
    'host'     => 'db',
    'port'     => 3306,
    'database' => 'shop',
    'username' => 'app',
    'password' => getenv('DB_PASS'),
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
]);
```

### PostgreSQL

```php
$db = new Connection([
    'driver'   => 'pgsql',
    'host'     => 'pg',
    'port'     => 5432,
    'database' => 'shop',
    'username' => 'app',
    'password' => getenv('DB_PASS'),
    'charset'  => '',   // pgsql does not use SET NAMES
]);
```

### SQLite

```php
$db = new Connection([
    'driver'   => 'sqlite',
    'database' => __DIR__ . '/app.sqlite',  // or ':memory:'
    'charset'  => '',
]);
```

## Lifecycle

```php
$db->getPDO();   // opens the connection on first call
$db->disconnect();// releases the in-process PDO reference
```

A few rules:

- **Once a PDO instance exists, credential setters throw.** Calling
  `setHost()` / `setDatabase()` / etc. after `getPDO()` raises
  `ConnectionAlreadyEstablishedException` (the historical
  `ValidConnectionAvailableException` is its parent, so old `catch` blocks
  still match).
- **`disconnect()` does not close persistent connections.** PHP returns
  them to the pool — see the note on `PDO::ATTR_PERSISTENT` below.

```php
use InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException;

$db->getPDO();
try {
    $db->setDatabase('other');
} catch (ConnectionAlreadyEstablishedException $e) {
    $db->disconnect();
    $db->setDatabase('other');
    $db->getPDO();
}
```

## Default PDO options

| Attribute                    | Default value             |
|------------------------------|---------------------------|
| `ATTR_EMULATE_PREPARES`      | `false`                   |
| `ATTR_PERSISTENT`            | `false` *(was true in 1.x)*|
| `ATTR_ERRMODE`               | `ERRMODE_EXCEPTION`       |
| `ATTR_DEFAULT_FETCH_MODE`    | `FETCH_ASSOC`             |

Any entry you put in the `options` credential overrides the default.
`setOptions()` is also available at runtime — until the connection has been
opened.

```php
$db = new Connection([
    'driver'   => 'mysql',
    'options'  => [
        PDO::ATTR_PERSISTENT => true,        // opt in to persistence
        PDO::ATTR_TIMEOUT    => 5,
    ],
]);
```

## Cloning

`clone()` returns a fresh, disconnected Connection that carries the same
credentials. Use it when you need a sibling connection (a second cursor,
parallel transaction, separate database) without re-wiring the configuration.

```php
$readReplica = $primary->clone()->setHost('replica');
```

## What's next

- [03 · Querying](03-querying.md) for the `query()` method itself.
- [05 · Transactions](05-transactions.md) for `beginTransaction`/`commit`.
