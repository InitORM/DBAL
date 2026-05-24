# Upgrade Guide

## From 1.x to 2.0

### Minimum PHP version

`initorm/dbal` now requires **PHP 8.0 or later**. If you are still on 7.4,
stay on the `1.x` branch.

```diff
- "require": { "initorm/dbal": "^1.0" }
+ "require": { "initorm/dbal": "^2.0" }
```

### Directory layout

Sources moved from the repository root to `src/`:

```
Connection/   →  src/Connection/
DataMapper/   →  src/DataMapper/
```

If you depend on the package through Composer's autoloader, nothing changes.
If your build scripts hard-coded the old paths (rare), update them.

### Persistent connections are now opt-in

`PDO::ATTR_PERSISTENT` defaults to `false`. In 1.x it was `true`, which
silently shared connections — and therefore transactions and prepared
statement caches — across requests under most SAPIs.

To restore the old behaviour:

```php
new Connection([
    // ...
    'options' => [
        \PDO::ATTR_PERSISTENT => true,
    ],
]);
```

### `DataMapper::rows()` returns `[]` instead of `null` when empty

```diff
- $rows = $mapper->rows();
- if ($rows === null) { ... }
+ $rows = $mapper->rows();
+ if ($rows === []) { ... }
```

The return type is now `array` (non-nullable).

### `DataMapper::bind()` uses `PDO::PARAM_BOOL` for booleans

Previously booleans were bound as `PARAM_INT`. PostgreSQL rejects integer
literals where a `boolean` is expected, so this was a latent bug. MySQL is
unaffected in practice. If you relied on the integer coercion, cast
explicitly:

```diff
- $mapper->bindValue('active', $isActive);          // was bound as INT(0|1)
+ $mapper->bindValue('active', (int) $isActive);     // explicit cast
```

### `ValidConnectionAvailableException` renamed

`InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException` is
the new name. The old class remains as a deprecated alias that extends the
new one, so existing `catch` blocks keep working — but you should rename
references at your earliest convenience.

### DSN auto-build now works

In 1.x, `Connection::getDsn()` had two bugs that made the auto-build branch
unreachable, and (when reached) put the charset value into `dbname=`. If you
were quietly passing a full `dsn` credential to work around this, you no
longer need to:

```php
new Connection([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'app',
    'charset'  => 'utf8mb4',
    // 'dsn' is now built correctly from the above
]);
```

### `createLog()` placeholders are PSR-3 style

Context keys must now be wrapped in braces in the message template:

```diff
- $connection->createLog('user logged in: id', ['id' => 42]);
+ $connection->createLog('user logged in: {id}', ['id' => 42]);
```

### PSR-3 logger support

You can now pass any `Psr\Log\LoggerInterface` as the `log` credential:

```php
new Connection([
    // ...
    'log' => $psr3Logger,
]);
```

Existing callable / file-path / `critical()` duck-typed loggers continue to
work.
