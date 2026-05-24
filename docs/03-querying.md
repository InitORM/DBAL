# 03 · Querying

## The `query()` method

```php
public function query(
    string $sqlQuery,
    ?array $parameters = null,
    ?array $options    = null
): DataMapperInterface;
```

Three things happen on every call:

1. `PDO::prepare()` is called with `$sqlQuery` and any prepare `$options`.
2. If `$parameters` is non-empty, each entry is bound via
   `DataMapper::bindValue()` with a type chosen by `bind()`.
3. `execute()` runs; the resulting `DataMapper` is returned for fetching.

If preparation or execution fails, the original PDO/Throwable exception is
re-thrown after the failure is recorded in the query log (when enabled) and
forwarded to the configured logger (when configured).

## Named parameters

Keys may include or omit the leading `:`:

```php
$db->query('SELECT * FROM users WHERE id = :id', ['id' => 1]);   // works
$db->query('SELECT * FROM users WHERE id = :id', [':id' => 1]);  // also works
```

Type selection is automatic:

| PHP type | PDO bind type   |
|----------|-----------------|
| `bool`   | `PARAM_BOOL`    |
| `int`    | `PARAM_INT`     |
| `null`   | `PARAM_NULL`    |
| other    | `PARAM_STR`     |

## Prepare options

Two layers: connection-wide `queryOptions` and per-call `$options`. The
per-call value wins:

```php
use PDO;

$db = new Connection([
    'queryOptions' => [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY],
]);

$db->query(
    'SELECT * FROM big_table',
    null,
    [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]   // overrides for this call only
);
```

> **Why `+` not `array_merge`?** PDO prepare options use integer keys. In
> 1.x the merge was done with `array_merge`, which renumbers integer keys
> and silently broke the option passing. 2.x uses union (`+`) so positional
> integrity is preserved.

## Error handling

`Connection::query()` re-throws whatever PDO raises. With the default
`ERRMODE_EXCEPTION`, that is normally a `PDOException`:

```php
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;

try {
    $db->query('SELECT * FROM nope');
} catch (SQLExecuteException $e) {
    // raised when prepare/execute themselves report failure
} catch (\PDOException $e) {
    // raised by PDO (most common case under ERRMODE_EXCEPTION)
}
```

The failure is forwarded to your logger before the exception bubbles up, so
you do not need to log inside the catch.

## Query logging

Enable the in-memory buffer to inspect what ran during a request:

```php
$db = new Connection(['queryLogs' => true]);
$db->query('SELECT 1');
$db->query('SELECT 2');

$db->getQueryLogs();
/*
[
    ['query' => 'SELECT 1', 'args' => null, 'timer' => 0.000123],
    ['query' => 'SELECT 2', 'args' => null, 'timer' => 0.000087],
]
*/
```

The buffer lives in process memory — clear or persist it yourself before it
grows unbounded.

## What's next

- [04 · DataMapper](04-data-mapper.md) for everything you can do with the
  returned wrapper.
